<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = "buildings";

    public function room()
    {
        return $this->hasMany(Room::class, 'building', 'building_id');
    }
}