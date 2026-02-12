<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDayOff extends Model
{
    protected $table = 'master_days_off';
    protected $primaryKey = 'id_day_off';
    public $timestamps = false;

    protected $fillable = [
        'id_master',
        'date_off',
        'reason',
    ];

    protected $casts = [
        'date_off' => 'date',
    ];

    public function master()
    {
        return $this->belongsTo(User::class, 'id_master', 'id_user');
    }
}
