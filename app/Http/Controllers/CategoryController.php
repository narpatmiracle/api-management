<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/categories-store",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","slug","title","description","image"},
     *                 @OA\Property(property="name", type="string", maxLength=255,example="product 1"),
     *                 @OA\Property(property="slug", type="string", maxLength=255,example="product-1"),
     *                 @OA\Property(property="title", type="string", maxLength=255,example="product add new"),
     *                 @OA\Property(property="description", type="string", maxLength=255,example="product add new in store."),
     *                 @OA\Property(property="image", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validate request data
            $validated = validator($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories',
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ])->validate();

            // Handle image upload
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            if (!$image->move(public_path('images'), $imageName)) {
                throw new \Exception('Failed to upload image');
            }

            // Create new category
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image' => $imageName
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => array_merge($category->toArray(), [
                    'image_url' => asset('images/' . $category->image)
                ]),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Delete uploaded image if category creation fails
            if (isset($imageName) && file_exists(public_path('images/' . $imageName))) {
                unlink(public_path('images/' . $imageName));
            }

            return response()->json([
                'status' => false,
                'message' => 'Error creating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all categories",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index()
    {
        try {
            $categories = Category::all();
            return response()->json([
                'status' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get category by ID",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $category
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\post(
     *     path="/api/categories/{id}",
     *     summary="Update category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"name","slug","title","description"},
     *                 @OA\Property(property="name", type="string", maxLength=255,example="product 1"),
     *                 @OA\Property(property="slug", type="string", maxLength=255,example="product-1"),
     *                 @OA\Property(property="title", type="string", maxLength=255,example="product add new"),
     *                 @OA\Property(property="description", type="string", maxLength=255,example="product add new in store."),
     *                 @OA\Property(property="image", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = validator($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ])->validate();

            $category = Category::findOrFail($id);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();

                if (!$image->move(public_path('images'), $imageName)) {
                    throw new \Exception('Failed to upload image');
                }

                // Delete old image
                if (file_exists(public_path('images/' . $category->image))) {
                    unlink(public_path('images/' . $category->image));
                }

                $validated['image'] = $imageName;
            }

            $category->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Delete category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);

            // Delete associated image
            if (file_exists(public_path('images/' . $category->image))) {
                unlink(public_path('images/' . $category->image));
            }

            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
