<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'description',
        'fish_species',
        'recommendedTechniques',
        'depth',
        'user_id',
        'upvotes',
        'downvotes',
        'voter_ids'
    ];
    protected $casts = [
        'voter_ids' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'depth' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
