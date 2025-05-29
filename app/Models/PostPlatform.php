<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostPlatform extends Model
{
    protected $fillable = [
        'post_id',
        'platform_id',
        'platform_status'
    ];

    /**
     * Get the post that owns the platform.
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the platform that owns the post.
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
