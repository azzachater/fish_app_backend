<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Event extends Model
{

    use HasFactory;
    protected $fillable = [
        'title',
        'location',
        'description',
        'date',
        'user_id'
    ];
    protected $casts = [
        'date' => 'datetime:Y-m-d'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participants');
    }
    // Helper pour récupérer les avatars
    public function getParticipantAvatars()
    {
        return $this->participants->map(function ($participant) {
            return $participant->user->avatar_url; // Adaptez à votre structure User
        })->toArray();
    }
}