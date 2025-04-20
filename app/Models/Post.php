<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_text',
        'post_image',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['like_count', 'is_liked'];

    public function getLikeCountAttribute()
    {
        return $this->likes()->count();
    }
    
    public function getIsLikedAttribute()
    {
        return $this->likes()->where('user_id', auth()->id())->exists();
    }
    
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
