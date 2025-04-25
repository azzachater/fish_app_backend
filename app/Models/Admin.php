<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ← ajoute cette ligne

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable; // ← ajoute HasApiTokens ici

    // ton code existant (attributs, etc.)
}
