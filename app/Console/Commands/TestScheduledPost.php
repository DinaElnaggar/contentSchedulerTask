<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestScheduledPost extends Command
{
    protected $signature = 'test:scheduled-post';
    protected $description = 'Create a test scheduled post and process it';

    public function handle()
    {
        // 1. Create test user if not exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password123'),
            ]
        );

        // 2. Create test platforms if not exist
        $platforms = [];
        $platformTypes = ['twitter', 'facebook', 'instagram'];
        
        foreach ($platformTypes as $type) {
            $platforms[] = Platform::firstOrCreate(
                ['type' => $type],
                ['name' => ucfirst($type)]
            );
        }

        // 3. Create a scheduled post
        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Test Scheduled Post ' . now()->format('Y-m-d H:i:s'),
            'content' => 'This is a test scheduled post content.',
            'status' => 'scheduled',
            'scheduled_time' => now()->addMinute(), // Schedule for 1 minute from now
        ]);

        // 4. Attach platforms to the post
        $post->platforms()->attach(collect($platforms)->pluck('id'));

        $this->info('Created test post ID: ' . $post->id);
        $this->info('Scheduled for: ' . $post->scheduled_time);
        $this->info('Attached platforms: ' . implode(', ', $platformTypes));
        
        // 5. Run the scheduler immediately to process it
        $this->info("\nProcessing scheduled posts...");
        $this->call('posts:process-scheduled');
        
        // 6. Display results
        $post->refresh();
        $this->info("\nPost status: " . $post->status);
        
        foreach ($post->platforms as $platform) {
            $this->info($platform->type . ' status: ' . $platform->pivot->platform_status);
        }
    }
} 