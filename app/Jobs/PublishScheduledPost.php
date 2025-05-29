<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\PlatformPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The post instance.
     *
     * @var \App\Models\Post
     */
    protected $post;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(PlatformPublisher $publisher): void
    {
        try {
            // Check if post still exists and is still scheduled
            if (!$this->post || $this->post->status !== 'scheduled') {
                return;
            }

            // Get all platforms for this post
            $platforms = $this->post->platforms;
            
            $allSuccess = true;

            // Try to publish to each platform
            foreach ($platforms as $platform) {
                try {
                    // Get the pivot record
                    $postPlatform = $this->post->platforms()->where('platform_id', $platform->id)->first()->pivot;

                    // Skip if already published
                    if ($postPlatform->platform_status === 'published') {
                        continue;
                    }

                    // Attempt to publish
                    $publisher->publish($this->post, $platform);

                    // Update pivot with success
                    $postPlatform->update([
                        'platform_status' => 'published'
                    ]);

                } catch (\Exception $e) {
                    $allSuccess = false;

                    // Update pivot with failure
                    $postPlatform->update([
                        'platform_status' => 'failed'
                    ]);

                    Log::error("Failed to publish post to platform", [
                        'post_id' => $this->post->id,
                        'platform' => $platform->type,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update post status only if all platforms were successful
            if ($allSuccess) {
                $this->post->update([
                    'status' => 'published'
                ]);

                Log::info('Successfully published post to all platforms', [
                    'post_id' => $this->post->id,
                    'title' => $this->post->title
                ]);
            } else {
                throw new \Exception('Failed to publish to one or more platforms');
            }

        } catch (\Exception $e) {
            Log::error('Failed to publish scheduled post', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
} 