<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'zapis';
    protected $primaryKey = 'id_zapis';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'yslygi_id',
        'date_time',
        'id_master',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'yslygi_id', 'id_yslygi');
    }

    public function master()
    {
        return $this->belongsTo(User::class, 'id_master', 'id_user');
    }
}
