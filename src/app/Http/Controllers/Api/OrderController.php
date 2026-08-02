<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'purchasable_id' => 'required|string',
            'purchasable_type' => 'required|string|in:product,bundle',
        ]);

        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'طالب غير موجود.'
            ], 404);
        }

        if (!$student->is_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'عفواً، لا تملك الصلاحيات لشراء هذا المحتوى. يرجى التواصل مع الإدارة.'
            ], 403);
        }

        $type = $request->input('purchasable_type');
        $id = $request->input('purchasable_id');

        $purchasableClass = $type === 'product' ? Product::class : Bundle::class;
        $purchasable = $purchasableClass::find($id);

        if (!$purchasable) {
            return response()->json([
                'status' => 'error',
                'message' => 'المنتج غير موجود.'
            ], 404);
        }

        if ($purchasable instanceof Product && !$purchasable->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا المنتج غير متاح حالياً.'
            ], 400);
        }

        // Idempotency / Duplicate pending order check
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? 'IDEMP-' . md5($student->id . '-' . $purchasableClass . '-' . $id);
        
        $existingOrder = Order::where('student_id', $student->id)
            ->where('purchasable_type', $purchasableClass)
            ->where('purchasable_id', $id)
            ->where('status', \App\Enums\OrderStatus::Pending->value)
            ->first();

        if ($existingOrder) {
            return response()->json([
                'status' => 'success',
                'message' => 'يوجد طلب شراء معلق بالفعل لهذا المحتوى.',
                'data' => $existingOrder,
            ], 200);
        }

        $order = Order::create([
            'student_id' => $student->id,
            'purchasable_id' => $purchasable->id,
            'purchasable_type' => $purchasableClass,
            'amount_cents' => (int) round((float) $purchasable->price * 100),
            'currency' => 'EGP',
            'payment_method' => 'manual',
            'transaction_id' => 'PENDING-' . strtoupper((string) \Illuminate\Support\Str::uuid()),
            'status' => \App\Enums\OrderStatus::Pending->value,
            'idempotency_key' => $idempotencyKey,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب الشراء بنجاح. سيتم تفعيل المحتوى بعد التحقق من الدفع.',
            'data' => $order,
        ], 201);
    }
}
