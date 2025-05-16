<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = ['user_id', 'event_id']; // Les clés étrangères

    // Lien vers l'utilisateur (participant)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Lien vers l'événement
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}