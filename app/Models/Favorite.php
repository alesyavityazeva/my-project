<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $table = 'favorites';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'yslygi_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'yslygi_id', 'id_yslygi');
    }
}
