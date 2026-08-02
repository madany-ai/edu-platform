<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Lecture;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->instructor = User::create([
        'name' => 'Instructor User',
        'email' => 'instructor_pay@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->studentUser = User::create([
        'name' => 'Student User',
        'email' => 'student_pay@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Kareem',
        'second_name' => 'Ali',
        'third_name' => 'Mohamed',
        'last_name' => 'Saad',
        'phone' => '01099998888',
        'father_phone' => '01199998888',
        'mother_phone' => '01299998888',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2004-03-10',
        'is_verified' => true,
    ]);

    $this->course = Course::create([
        'title' => 'Payment Course',
        'description' => 'Course description',
        'price' => 150.00,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => 'Section 1',
        'sort_order' => 1,
    ]);

    $this->lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Paid Lecture',
        'description' => 'Paid lecture description',
        'duration' => 30,
        'sort_order' => 1,
        'status' => 'published',
    ]);

    $this->product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Paid Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 150.00,
        'is_active' => true,
    ]);
});

test('Paymob Webhook verifies signature, updates order to completed and grants entitlement', function () {
    config(['services.paymob.hmac_secret' => 'test_hmac_secret']);

    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->product->id,
        'purchasable_type' => Product::class,
        'amount_cents' => 15000,
        'currency' => 'EGP',
        'payment_method' => 'paymob',
        'gateway_reference' => '1234567',
        'status' => 'pending',
    ]);

    $payloadObj = [
        'amount_cents' => 15000,
        'created_at' => '2026-08-02T12:00:00',
        'currency' => 'EGP',
        'error_occured' => false,
        'has_parent_transaction' => false,
        'id' => 999888,
        'integration_id' => 12345,
        'is_3d_secure' => true,
        'is_auth' => false,
        'is_capture' => false,
        'is_refunded' => false,
        'is_standalone_payment' => true,
        'pending' => false,
        'order' => ['id' => 1234567],
        'owner' => 100,
        'source_data' => [
            'pan' => '2345',
            'sub_type' => 'MasterCard',
            'type' => 'Card',
        ],
        'success' => true,
    ];

    // Compute HMAC for payloadObj
    $keys = [
        'amount_cents', 'created_at', 'currency', 'error_occured',
        'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
        'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'pending', 'order.id', 'owner', 'pending', 'source_data.pan',
        'source_data.sub_type', 'source_data.type', 'success',
    ];

    $concat = '';
    foreach ($keys as $k) {
        $v = data_get($payloadObj, $k);
        if (is_bool($v)) $v = $v ? 'true' : 'false';
        $concat .= $v;
    }
    $hmac = hash_hmac('sha512', $concat, 'test_hmac_secret');

    $response = $this->postJson("/api/webhooks/paymob?hmac={$hmac}", [
        'obj' => $payloadObj,
    ]);

    $response->assertStatus(200);

    $order->refresh();
    expect($order->status->value)->toBe('completed');
    expect($order->paid_at)->not->toBeNull();

    // Verify entitlement was granted
    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

test('Paymob Webhook rejects invalid HMAC signature with 403', function () {
    config(['services.paymob.hmac_secret' => 'test_hmac_secret']);

    $response = $this->postJson('/api/webhooks/paymob?hmac=invalid_signature', [
        'obj' => ['amount_cents' => 1000],
    ]);

    $response->assertStatus(403);
});
