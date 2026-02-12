<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'yslygi';
    protected $primaryKey = 'id_yslygi';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'opisanie',
        'foto',
        'duration_minutes',
        'id_kategori',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_kategori', 'id_kategori');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'yslygi_id', 'id_yslygi');
    }

    public function masters()
    {
        return $this->belongsToMany(User::class, 'master_services', 'id_yslygi', 'id_master', 'id_yslygi', 'id_user');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'yslygi_id', 'user_id', 'id_yslygi', 'id_user');
    }
}
