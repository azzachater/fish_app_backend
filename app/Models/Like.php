<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    // Define the fillable attributes
    protected $fillable = ['user_id', 'post_id'];

    // Relation to Post (a Like belongs to one Post)
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Relation to User (a Like belongs to one User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
