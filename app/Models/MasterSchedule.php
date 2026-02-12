<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSchedule extends Model
{
    protected $table = 'master_schedule';
    public $timestamps = false;

    protected $fillable = [
        'id_master',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    public function master()
    {
        return $this->belongsTo(User::class, 'id_master', 'id_user');
    }
}
