<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCheckin extends Model
{
    protected $table = "booking_checkins";
    protected $primaryKey = 'book_id';
    public $incrementing = false; //ไม่ใช้ options auto increment
    // public $timestamps = false; //ไม่ใช้ field updated_at และ created_at

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'book_id', 'book_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}