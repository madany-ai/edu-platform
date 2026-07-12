<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Bundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('is_active', true);

        // Filter by type if provided (lecture, section, course)
        if ($type = $request->query('type')) {
            $classMap = [
                'course' => \App\Models\Course::class,
                'section' => \App\Models\CourseSection::class,
                'lecture' => \App\Models\Lecture::class,
            ];
            if (isset($classMap[$type])) {
                $query->where('sellable_type', $classMap[$type]);
            }
        }

        $products = $query->with('sellable')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function bundles(): JsonResponse
    {
        $bundles = Bundle::with('products.sellable')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $bundles
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->sellable_type === \App\Models\CourseSection::class) {
            $product->load('sellable.lectures');
        } elseif ($product->sellable_type === \App\Models\Course::class) {
            $product->load('sellable.sections.lectures');
        } else {
            $product->load('sellable');
        }

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    public function showBundle(Bundle $bundle): JsonResponse
    {
        $bundle->load(['products.sellable']);
        
        return response()->json([
            'status' => 'success',
            'data' => $bundle
        ]);
    }
}
