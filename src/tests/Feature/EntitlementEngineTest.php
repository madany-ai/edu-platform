<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\Entitlement;
use App\Services\GrantEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;
    protected Student $student;
    protected Course $course;
    protected CourseSection $section;
    protected Lecture $lecture1;
    protected Lecture $lecture2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::create([
            'name' => 'Instructor',
            'email' => 'instructor@test.com',
            'password' => bcrypt('password'),
        ]);

        $studentUser = User::create([
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'first_name' => 'John',
            'second_name' => 'Middle1',
            'third_name' => 'Middle2',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'father_phone' => '0555555555',
            'mother_phone' => '0555555556',
            'guardian_job' => 'Engineer',
            'gender' => 'male',
            'birth_date' => '2005-01-01',
        ]);

        $this->course = Course::create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 10.00,
            'status' => 'published',
            'instructor_id' => $this->instructor->id,
        ]);

        $this->section = CourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Section 1',
            'sort_order' => 1,
        ]);

        $this->lecture1 = Lecture::create([
            'section_id' => $this->section->id,
            'title' => 'Lecture 1',
            'description' => 'Lecture 1 Description',
            'duration' => 10,
            'sort_order' => 1,
        ]);

        $this->lecture2 = Lecture::create([
            'section_id' => $this->section->id,
            'title' => 'Lecture 2',
            'description' => 'Lecture 2 Description',
            'duration' => 15,
            'sort_order' => 2,
        ]);
    }

    public function test_granting_entitlement_for_single_lecture_product()
    {
        $product = Product::create([
            'instructor_id' => $this->instructor->id,
            'name' => 'Single Lecture 1',
            'sellable_id' => $this->lecture1->id,
            'sellable_type' => Lecture::class,
            'price_cents' => 1000,
            'access_duration_days' => 30,
        ]);

        $order = Order::create([
            'student_id' => $this->student->id,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class,
            'amount_cents' => 1000,
            'status' => 'completed',
        ]);

        (new GrantEntitlementService())->handle($order);

        $this->assertDatabaseHas('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture1->id,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseMissing('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture2->id,
        ]);

        $entitlement = Entitlement::where('lecture_id', $this->lecture1->id)->first();
        $this->assertNotNull($entitlement->expires_at);
        $this->assertTrue($entitlement->expires_at->isAfter(now()));
    }

    public function test_granting_entitlement_for_section_product()
    {
        $product = Product::create([
            'instructor_id' => $this->instructor->id,
            'name' => 'Full Section 1',
            'sellable_id' => $this->section->id,
            'sellable_type' => CourseSection::class,
            'price_cents' => 3000,
            'access_duration_days' => 60,
        ]);

        $order = Order::create([
            'student_id' => $this->student->id,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class,
            'amount_cents' => 3000,
            'status' => 'completed',
        ]);

        (new GrantEntitlementService())->handle($order);

        $this->assertDatabaseHas('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture1->id,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture2->id,
            'order_id' => $order->id,
        ]);

        $entitlement1 = Entitlement::where('lecture_id', $this->lecture1->id)->first();
        $entitlement2 = Entitlement::where('lecture_id', $this->lecture2->id)->first();

        $this->assertNotNull($entitlement1->expires_at);
        $this->assertNotNull($entitlement2->expires_at);
    }

    public function test_granting_entitlement_for_bundle()
    {
        $product1 = Product::create([
            'instructor_id' => $this->instructor->id,
            'name' => 'Single Lecture 1',
            'sellable_id' => $this->lecture1->id,
            'sellable_type' => Lecture::class,
            'price_cents' => 1000,
            'access_duration_days' => 15,
        ]);

        $product2 = Product::create([
            'instructor_id' => $this->instructor->id,
            'name' => 'Single Lecture 2',
            'sellable_id' => $this->lecture2->id,
            'sellable_type' => Lecture::class,
            'price_cents' => 1000,
            'access_duration_days' => 30,
        ]);

        $bundle = Bundle::create([
            'instructor_id' => $this->instructor->id,
            'name' => 'Bundle of Both',
            'price_cents' => 1500,
        ]);

        $bundle->products()->attach([$product1->id, $product2->id]);

        $order = Order::create([
            'student_id' => $this->student->id,
            'purchasable_id' => $bundle->id,
            'purchasable_type' => Bundle::class,
            'amount_cents' => 1500,
            'status' => 'completed',
        ]);

        (new GrantEntitlementService())->handle($order);

        $this->assertDatabaseHas('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture1->id,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('entitlements', [
            'student_id' => $this->student->id,
            'lecture_id' => $this->lecture2->id,
            'order_id' => $order->id,
        ]);

        $entitlement1 = Entitlement::where('lecture_id', $this->lecture1->id)->first();
        $entitlement2 = Entitlement::where('lecture_id', $this->lecture2->id)->first();

        // Expire days should match respective products even when bought in a bundle
        $this->assertEquals(15, (int) round(now()->diffInDays($entitlement1->expires_at)));
        $this->assertEquals(30, (int) round(now()->diffInDays($entitlement2->expires_at)));
    }
}
