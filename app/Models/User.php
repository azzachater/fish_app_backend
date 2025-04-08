<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Profil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     * 
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $table = 'users';
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function products()
    {
        return $this->hasMany(product::class);
    }
    public function fishingLogs()
    {
        return $this->hasMany(FishingLog::class);
    }
    public function tips()
    {
        return $this->hasMany(Tip::class);
    }

    public function spots(){
        return $this->hasMany(Spot::class);
    }
    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function profil(){
        return $this->hasOne(Profil::class);
    }
    // app/Models/User.php

public function profile()
{
    return $this->hasOne(Profile::class);
}

public function sentMessages()
{
    return $this->hasMany(Message::class, 'sender_id');
}

public function conversations()
{
    return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id);
}  

}
