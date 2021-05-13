<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = "user_permission";

    public function user()
    {
        return $this->hasOne(User::class, 'loginname', 'loginname');
    }
}