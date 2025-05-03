<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetCode extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'code', 'created_at'];

    public function isExpired(): bool
    {
        return Carbon::parse($this->created_at)->addMinutes(10)->isPast();
    }
}
