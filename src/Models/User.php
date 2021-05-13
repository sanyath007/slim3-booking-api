<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = "hos";
    protected $table = "opduser";

    public function permission()
    {
        return $this->setConnection('default')->belongsTo(UserPermission::class, 'loginname', 'loginname');
    }
}