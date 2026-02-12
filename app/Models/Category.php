<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;

    protected $fillable = ['name'];

    public function services()
    {
        return $this->hasMany(Service::class, 'id_kategori', 'id_kategori');
    }
}
