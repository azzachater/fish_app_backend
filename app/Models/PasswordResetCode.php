<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetCode extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'code', 'expires_at', 'created_at'];

    public function isExpired()
{
    return now()->gt($this->expires_at);
}
}
