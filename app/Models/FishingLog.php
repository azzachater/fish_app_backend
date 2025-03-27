<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FishingLog extends Model
    {
        use HasFactory;
    
        protected $fillable = [
            'user_id',
            'date',
            'location',
            'species_caught',
            'fishing_conditions',
            'notes',
        ];
    
        public function user()
        {
            return $this->belongsTo(User::class);
        }
}
