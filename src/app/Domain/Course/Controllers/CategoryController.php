<?php

namespace App\Domain\Course\Controllers;

use App\Domain\Shared\Controllers\Controller;
use App\Domain\Course\Resources\CategoryResource;
use App\Domain\Course\Services\CategoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->categoryService->listActive();

        return CategoryResource::collection($categories);
    }
}
