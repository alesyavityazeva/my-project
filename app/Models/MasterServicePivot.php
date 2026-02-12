<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterServicePivot extends Model
{
    protected $table = 'master_services';
    public $timestamps = false;

    protected $fillable = [
        'id_master',
        'id_yslygi',
    ];

    public function master()
    {
        return $this->belongsTo(User::class, 'id_master', 'id_user');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_yslygi', 'id_yslygi');
    }
}
