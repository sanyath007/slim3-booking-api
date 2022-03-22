<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingNewborn extends Model
{
    protected $table = "booking_newborns";

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'book_id', 'book_id');
    }

    public function ip()
    {
        return $this->belongsTo(Ip::class, 'an', 'an');
    }
}