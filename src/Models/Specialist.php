<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    protected $table = "specialists";

    public function booking()
    {
        return $this->hasMany(Booking::class, 'specialist', 'id');
    }
}