<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Twitter',
                'type' => 'twitter',
             
            ],
            [
                'name' => 'Facebook',
                'type' => 'facebook',
              
            ],
            [
                'name' => 'Instagram',
                'type' => 'instagram',
              
            ]
        ];

        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
} 