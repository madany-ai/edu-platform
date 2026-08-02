<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Http\Resources\BundleResource;
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

        $products = $query->with('sellable')->latest()->paginate($request->get('per_page', 15));
        $paginated = ProductResource::collection($products)->response()->getData(true);

        return response()->json([
            'status' => 'success',
            'data' => $paginated['data'] ?? [],
            'links' => $paginated['links'] ?? null,
            'meta' => $paginated['meta'] ?? null,
        ]);
    }

    public function bundles(Request $request): JsonResponse
    {
        $bundles = Bundle::with('products.sellable')->latest()->paginate($request->get('per_page', 15));
        $paginated = BundleResource::collection($bundles)->response()->getData(true);

        return response()->json([
            'status' => 'success',
            'data' => $paginated['data'] ?? [],
            'links' => $paginated['links'] ?? null,
            'meta' => $paginated['meta'] ?? null,
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
            'data' => new ProductResource($product)
        ]);
    }

    public function showBundle(Bundle $bundle): JsonResponse
    {
        $bundle->load(['products.sellable']);
        
        return response()->json([
            'status' => 'success',
            'data' => new BundleResource($bundle)
        ]);
    }
}
