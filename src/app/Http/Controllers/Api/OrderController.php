<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Student;
use App\Services\GrantEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request, GrantEntitlementService $grantService): JsonResponse
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

        $order = DB::transaction(function () use ($student, $purchasable, $purchasableClass, $grantService) {
            $order = Order::create([
                'student_id' => $student->id,
                'purchasable_id' => $purchasable->id,
                'purchasable_type' => $purchasableClass,
                'amount_cents' => intval($purchasable->price * 100),
                'currency' => 'EGP',
                'payment_method' => 'mock',
                'transaction_id' => 'MOCK-' . strtoupper(uniqid()),
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $grantService->handle($order);

            return $order;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم شراء المنتج بنجاح وتفعيل المحتوى العلمي.',
            'data' => $order,
        ], 201);
    }
}
