<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Profil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail
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
        'verification_code',
        'verification_code_expires_at'
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
        'verification_code'
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
            'verification_code_expires_at' => 'datetime'
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

    public function spots()
    {
        return $this->hasMany(Spot::class);
    }
    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function profil()
    {
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
    public function sendEmailVerificationNotification()
    {
        // Désactivez l'envoi automatique pour utiliser notre version personnalisée
        // Ne faites rien ici, tout est géré dans le contrôleur
    }

    public function groupConversations()
    {
        return $this->belongsToMany(GroupConversation::class, 'group_conversation_user', 'user_id', 'group_conversation_id');
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'receiver_id');
    }
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    public function eventsParticipating()
{
    return $this->belongsToMany(Event::class, 'participants')
                ->using(Participant::class)
                ->withTimestamps();
}
}