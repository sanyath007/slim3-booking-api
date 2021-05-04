<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $table = "amenities";

    public function room()
    {
        return $this->hasMany(RoomAmenities::class, 'amenity_id', 'amenity_id');
    }
}