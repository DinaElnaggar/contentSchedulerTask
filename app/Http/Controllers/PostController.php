<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Http\Resources\PostResource;

class PostController extends Controller
{
    /**
     * Create a new post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Handle platform_ids from form-data
            $platformIds = $request->input('platform_ids');
            if (is_string($platformIds)) {
                $platformIds = explode(',', $platformIds);
                $request->merge(['platform_ids' => $platformIds]);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'scheduled_time' => 'nullable|date|after:now',
                'status' => 'required|in:draft,published,scheduled',
                'platform_ids' => 'required|array',
                'platform_ids.*' => 'required|exists:platforms,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the authenticated user
            $user = Auth::user();

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('posts', 'public');
            }

            // Create the post
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'image' => $imagePath,
                'status' => $request->status,
                'scheduled_time' => $request->scheduled_time
            ]);

            // Attach platforms
            $post->platforms()->attach($platformIds);

            // Handle scheduled posts
            if ($request->status === 'scheduled' && empty($request->scheduled_time)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Scheduled time is required for scheduled posts',
                ], 422);
            }

            // Load platforms relationship for response
            $post->load('platforms:id,name,type');

            return response()->json([
                'status' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'post' => $post,
                    'image_url' => $imagePath ? Storage::url($imagePath) : null,
                    'platforms' => $post->platforms
                ]
            ], 201);

        } catch (\Exception $e) {
            // Delete uploaded image if post creation fails
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'status' => false,
                'message' => 'Error creating post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get posts filtered by status and date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Post::query()->with(['platforms']);

            // Apply filters
            if ($request->has('platform')) {
                $query->whereHas('platforms', function ($q) use ($request) {
                    $q->where('platforms.id', $request->platform);
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date')) {
                $query->where('scheduled_at', '>=', Carbon::parse($request->start_date));
            }

            if ($request->has('end_date')) {
                $query->where('scheduled_at', '<=', Carbon::parse($request->end_date));
            }

            $posts = $query->orderBy('scheduled_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Posts retrieved successfully',
                'data' => PostResource::collection($posts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a scheduled post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Get the authenticated user's post
            $post = Post::where('user_id', Auth::id())->findOrFail($id);

            // Check if the post is scheduled
            if ($post->status !== 'scheduled') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only scheduled posts can be updated',
                ], 422);
            }

            // Get current date and time
            $now = Carbon::now();

            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string|max:255',
                'content' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'scheduled_time' => [
                    'nullable',
                    'date_format:Y-m-d H:i:s',
                    function ($attribute, $value, $fail) use ($now) {
                        $scheduledTime = Carbon::parse($value);
                        if ($scheduledTime <= $now) {
                            $fail('The scheduled time must be in the future.');
                        }
                    }
                ],
                'status' => 'nullable|in:draft,published,scheduled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($post->image) {
                    Storage::disk('public')->delete($post->image);
                }
                
                $image = $request->file('image');
                $imagePath = $image->store('posts', 'public');
                $post->image = $imagePath;
            }

            // Update fields if provided
            if ($request->has('title')) {
                $post->title = $request->title;
            }
            if ($request->has('content')) {
                $post->content = $request->content;
            }

            // Handle status and scheduled_time changes
            if ($request->has('status')) {
                $newStatus = $request->status;
                
                if ($newStatus === 'scheduled') {
                    // If keeping or changing to scheduled, ensure we have a valid scheduled_time
                    if (!$request->has('scheduled_time') && !$post->scheduled_time) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Scheduled time is required for scheduled posts',
                        ], 422);
                    }
                } else {
                    // If changing to non-scheduled status, clear the scheduled_time
                    $post->scheduled_time = null;
                }
                
                $post->status = $newStatus;
            }

            // Update scheduled_time if provided
            if ($request->has('scheduled_time')) {
                $post->scheduled_time = $request->scheduled_time;
            }

            $post->save();

            return response()->json([
                'status' => true,
                'message' => 'Post updated successfully',
                'data' => [
                    'post' => [
                        'id' => $post->id,
                        'title' => $post->title,
                        'content' => $post->content,
                        'status' => $post->status,
                        'scheduled_time' => $post->scheduled_time,
                        'image_url' => $post->image ? Storage::url($post->image) : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            // Delete newly uploaded image if update fails
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'status' => false,
                'message' => 'Error updating post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete a post.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            // Get the authenticated user's post
            $post = Post::where('user_id', Auth::id())->findOrFail($id);

            // Soft delete the post
            $post->delete();

            return response()->json([
                'status' => true,
                'message' => 'Post deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'platforms' => 'required|array',
            'platforms.*' => 'exists:platforms,id'
        ]);

        $post = Post::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image' => $validated['image'],
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled'
        ]);

        $post->platforms()->attach($validated['platforms']);

        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'data' => new PostResource($post->load('platforms'))
        ]);
    }

    public function show(Post $post)
    {
        return response()->json([
            'status' => true,
            'message' => 'Post retrieved successfully',
            'data' => new PostResource($post->load('platforms'))
        ]);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json([
            'status' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
} 