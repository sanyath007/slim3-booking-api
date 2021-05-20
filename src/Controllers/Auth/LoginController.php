<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use Firebase\JWT\JWT;
use Tuupola\Base62;
use App\Models\User;

class LoginController extends Controller
{
    public function login($req, $res, $args)
    {
        $params = $req->getParsedBody() ? : [];

        if($this->auth->attempt($params['username'], $params['password'])) {
            $now = new \DateTime();
            $future = new \DateTime("+30 minutes");
            $jti = (new Base62)->encode(random_bytes(16));
            $user = $this->auth->getUser();
            
            $payload = [
                "iat"   => $now->getTimeStamp(),
                "exp"   => $future->getTimeStamp(),
                "jti"   => $jti,
                "sub"   => [
                    'username'  => $user->loginname,
                    'name'      => $user->name,
                    'position'  => $user->entryposition,
                    'role'      => $user->permission ? $user->permission->role : 3
                ]
            ];

            $secret = getenv("JWT_SECRET");            
            $token = JWT::encode($payload, $secret, "HS256");       

            return $res->withStatus(201)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($token, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            return $res->withStatus(401)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'message' => 'Unauthentiction !!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }

    public function logout()
    {
        
    }
}
