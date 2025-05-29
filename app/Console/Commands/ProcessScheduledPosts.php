<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledPost;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process posts that are scheduled for publication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $now = Carbon::now();
            
            // Get all scheduled posts that are due
            $duePosts = Post::where('status', 'scheduled')
                ->where('scheduled_time', '<=', $now)
                ->get();

            $count = $duePosts->count();
            if ($count === 0) {
                $this->info('No scheduled posts are due for publication.');
                return 0;
            }

            $this->info("Found {$count} posts due for publication.");

            // Process each due post
            foreach ($duePosts as $post) {
                try {
                    PublishScheduledPost::dispatch($post);
                    $this->line("Dispatched job for post ID: {$post->id}");
                } catch (\Exception $e) {
                    $this->error("Failed to dispatch job for post ID: {$post->id}");
                    Log::error('Failed to dispatch scheduled post job', [
                        'post_id' => $post->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info('Finished processing scheduled posts.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to process scheduled posts: ' . $e->getMessage());
            Log::error('Failed to process scheduled posts', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
} 