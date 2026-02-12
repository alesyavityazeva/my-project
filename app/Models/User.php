<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'user';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'lastname',
        'name',
        'firstname',
        'nomber_tel',
        'password',
        'id_roli',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Full name accessor
    public function getFullNameAttribute(): string
    {
        return trim("{$this->lastname} {$this->name} {$this->firstname}");
    }

    // Role checks
    public function isClient(): bool
    {
        return $this->id_roli === 0;
    }

    public function isMaster(): bool
    {
        return $this->id_roli === 1;
    }

    public function isAdmin(): bool
    {
        return $this->id_roli === 2;
    }

    // Relationships
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id_user');
    }

    public function masterBookings()
    {
        return $this->hasMany(Booking::class, 'id_master', 'id_user');
    }

    public function masterSchedules()
    {
        return $this->hasMany(MasterSchedule::class, 'id_master', 'id_user');
    }

    public function masterDaysOff()
    {
        return $this->hasMany(MasterDayOff::class, 'id_master', 'id_user');
    }

    public function masterServices()
    {
        return $this->belongsToMany(Service::class, 'master_services', 'id_master', 'id_yslygi', 'id_user', 'id_yslygi');
    }

    public function favorites()
    {
        return $this->belongsToMany(Service::class, 'favorites', 'user_id', 'yslygi_id', 'id_user', 'id_yslygi');
    }
}
