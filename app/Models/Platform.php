<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Get the posts for the platform.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_platforms')
                    ->withTimestamps();
    }
}
