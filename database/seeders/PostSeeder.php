<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\Platform;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user if not exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password123'),
            ]
        );

        // Get all platforms
        $platforms = Platform::all();

        // Create posts with different statuses and platform relationships
        $posts = [
            [
                'title' => 'Published Post',
                'content' => 'This is a published post that has been shared across platforms.',
                'status' => 'published',
                'scheduled_time' => Carbon::now()->subHour(),
                'platform_status' => 'published'
            ],
            [
                'title' => 'Scheduled Post - Today',
                'content' => 'This post is scheduled for later today.',
                'status' => 'scheduled',
                'scheduled_time' => Carbon::now()->addHours(2),
                'platform_status' => 'pending'
            ],
            [
                'title' => 'Scheduled Post - Tomorrow',
                'content' => 'This post is scheduled for tomorrow.',
                'status' => 'scheduled',
                'scheduled_time' => Carbon::now()->addDay(),
                'platform_status' => 'pending'
            ],
            [
                'title' => 'Draft Post',
                'content' => 'This is a draft post.',
                'status' => 'draft',
                'scheduled_time' => null,
                'platform_status' => null
            ]
        ];

        foreach ($posts as $postData) {
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $postData['title'],
                'content' => $postData['content'],
                'status' => $postData['status'],
                'scheduled_time' => $postData['scheduled_time'],
            ]);

            // For published and scheduled posts, attach all platforms
            if ($postData['status'] !== 'draft') {
                foreach ($platforms as $platform) {
                    $post->platforms()->attach($platform->id, [
                        'platform_status' => $postData['platform_status']
                    ]);
                }
            }
            // For draft posts, attach random platforms
            else {
                $randomPlatforms = $platforms->random(rand(1, $platforms->count()));
                foreach ($randomPlatforms as $platform) {
                    $post->platforms()->attach($platform->id, [
                        'platform_status' => $postData['platform_status']
                    ]);
                }
            }
        }
    }
} 