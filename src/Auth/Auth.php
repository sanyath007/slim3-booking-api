<?php

namespace App\Auth;

use App\Models\User;

class Auth
{
    protected $user;

    public function attempt($username, $password)
    {
        $user = User::with('permission')
                    ->where('loginname', $username)
                    ->get(['loginname', 'name', 'password', 'passweb', 'entryposition'])
                    ->first();

        if(!$user) {
            return false;
        }
        
        if(md5($password) == $user->passweb) {
            $this->user = $user;
            
            return true;
        }

        return false;
    }

    public function getUser()
    {
        if($this->user) return $this->user;
    }
}
