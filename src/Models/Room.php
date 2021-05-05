<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = "rooms";
    protected $primaryKey = "room_id";

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type', 'room_type_id');
    }
    
    public function roomGroup()
    {
        return $this->belongsTo(RoomGroup::class, 'room_group', 'room_group_id');
    }
    
    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id', 'building_id');
    }
    
    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class, 'room_id', 'room_id');
    }

    public function amenities()
    {
        return $this->hasMany(RoomAmenities::class, 'room_id', 'room_id');
    }
}