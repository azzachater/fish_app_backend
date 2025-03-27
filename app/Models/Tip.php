<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    //pour activer les factories(donnees fake pour tester une app plus faciliment )
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'title',
        'description',
        'category',
        'image',
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }

}
