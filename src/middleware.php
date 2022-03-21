<?php

use Tuupola\Middleware\HttpBasicAuthentication;

$container = $app->getContainer();

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $response
                ->withStatus(500)
                ->withHeader("Content-Type", "application/json")
                ->write($exception->getMessage());
    };
};

$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->addConnection($container['settings']['db_hos'], 'hos');
$capsule->addConnection($container['settings']['db_person'], 'person');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function($c) use ($capsule) {
    return $capsule;
};

$container['auth'] = function($c) {
    return new App\Auth\Auth;
};

$container['logger'] = function($c) {
    $logger = new Monolog\Logger('My_logger');
    $file_handler = new Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);

    return $logger;
};

$container['validator'] = function($c) {
    return new App\Validations\Validator;
};

$container['jwt'] = function($c) {
    return new StdClass;
};

// JWT middleware
$app->add(new Slim\Middleware\JwtAuthentication([
    "path"          => '/api',
    "logger"        => $container['logger'],
    "passthrough"   => ["/test"],
    "secret"        => getenv("JWT_SECRET"),
    "callback"      => function($req, $res, $args) use ($container) {
        $container['jwt'] = $args['decoded'];
    },
    "error"         => function($req, $res, $args) {
        $data["status"] = "0";
        $data["message"] = $args["message"];
        $data["data"] = "";
        
        return $res
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

// CORS middleware
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Controllers
$container['HomeController'] = function($c) {
    return new App\Controllers\HomeController($c);
};

$container['UserController'] = function($c) {
    return new App\Controllers\UserController($c);
};

$container['LoginController'] = function($c) {
    return new App\Controllers\Auth\LoginController($c);
};

$container['DashboardController'] = function($c) {
    return new App\Controllers\DashboardController($c);
};

$container['QueueController'] = function($c) {
    return new App\Controllers\QueueController($c);
};

$container['RoomController'] = function($c) {
    return new App\Controllers\RoomController($c);
};

$container['RoomTypeController'] = function($c) {
    return new App\Controllers\RoomTypeController($c);
};

$container['RoomGroupController'] = function($c) {
    return new App\Controllers\RoomGroupController($c);
};

$container['BuildingController'] = function($c) {
    return new App\Controllers\BuildingController($c);
};

$container['AmenityController'] = function($c) {
    return new App\Controllers\AmenityController($c);
};

$container['BookingController'] = function($c) {
    return new App\Controllers\BookingController($c);
};

$container['SpecialistController'] = function($c) {
    return new App\Controllers\SpecialistController($c);
};

/** Person Controllers */
$container['StaffController'] = function($c) {
    return new App\Controllers\StaffController($c);
};

$container['DeptController'] = function($c) {
    return new App\Controllers\DeptController($c);
};

/** Hosxp Controllers */
$container['PatientController'] = function($c) {
    return new App\Controllers\PatientController($c);
};

$container['WardController'] = function($c) {
    return new App\Controllers\WardController($c);
};

$container['WardMoveController'] = function($c) {
    return new App\Controllers\WardMoveController($c);
};

$container['IpController'] = function($c) {
    return new App\Controllers\IpController($c);
};

$container['NewbornController'] = function($c) {
    return new App\Controllers\NewbornController($c);
};
