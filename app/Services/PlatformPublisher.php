<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Platform;
use Illuminate\Support\Facades\Log;

class PlatformPublisher
{
    /**
     * Simulate publishing to a specific platform.
     *
     * @param Post $post
     * @param Platform $platform
     * @return array
     */
    public function publish(Post $post, Platform $platform): array
    {
        // Simulate random success/failure and processing time
        usleep(rand(100000, 1000000)); // Random delay between 0.1 and 1 second

        // Simulate 90% success rate
        $isSuccess = (rand(1, 100) <= 90);

        if (!$isSuccess) {
            throw new \Exception("Failed to publish to {$platform->type} - API Error");
        }

        // Mock response based on platform type
        $response = match($platform->type) {
            'twitter' => [
                'tweet_id' => 'tw_' . uniqid(),
                'url' => "https://twitter.com/status/" . uniqid(),
            ],
            'facebook' => [
                'post_id' => 'fb_' . uniqid(),
                'url' => "https://facebook.com/posts/" . uniqid(),
            ],
            'instagram' => [
                'media_id' => 'ig_' . uniqid(),
                'url' => "https://instagram.com/p/" . uniqid(),
            ],
            'linkedin' => [
                'activity_id' => 'li_' . uniqid(),
                'url' => "https://linkedin.com/posts/" . uniqid(),
            ],
            'youtube' => [
                'video_id' => 'yt_' . uniqid(),
                'url' => "https://youtube.com/watch?v=" . uniqid(),
            ],
            'tiktok' => [
                'video_id' => 'tt_' . uniqid(),
                'url' => "https://tiktok.com/@user/video/" . uniqid(),
            ],
            default => [
                'post_id' => 'other_' . uniqid(),
                'url' => "https://platform.com/posts/" . uniqid(),
            ],
        };

        // Log the mock publishing
        Log::info("Mock published to {$platform->type}", [
            'post_id' => $post->id,
            'platform' => $platform->type,
            'response' => $response
        ]);

        return $response;
    }
} 