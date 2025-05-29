<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformController extends Controller
{
    /**
     * Get all platforms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $platforms = Platform::all();

            return response()->json([
                'status' => true,
                'message' => 'Platforms retrieved successfully',
                'data' => [
                    'platforms' => $platforms
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving platforms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new platform.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:platforms',
                'type' => 'required|in:twitter,instagram,facebook,linkedin,youtube,tiktok,other'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $platform = Platform::create([
                'name' => $request->name,
                'type' => $request->type
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Platform created successfully',
                'data' => [
                    'platform' => $platform
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating platform',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a platform.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $platform = Platform::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255|unique:platforms,name,' . $id,
                'type' => 'nullable|in:twitter,instagram,facebook,linkedin,youtube,tiktok,other'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->has('name')) {
                $platform->name = $request->name;
            }
            if ($request->has('type')) {
                $platform->type = $request->type;
            }

            $platform->save();

            return response()->json([
                'status' => true,
                'message' => 'Platform updated successfully',
                'data' => [
                    'platform' => $platform
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating platform',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a platform.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            $platform = Platform::findOrFail($id);
            $platform->delete();

            return response()->json([
                'status' => true,
                'message' => 'Platform deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting platform',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
