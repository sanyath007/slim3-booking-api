<?php

$app->options('/{routes:.+}', function($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/', 'HomeController:home')->setName('home');

$app->post('/login', 'LoginController:login')->setName('login');

$app->get('/dashboard/or-visit/{month}', 'DashboardController:orVisitMonth');
$app->get('/dashboard/or-type/{month}', 'DashboardController:orTypeMonth');

$app->get('/rooms', 'RoomController:getAll');
$app->get('/rooms/{id}', 'RoomController:getById');
$app->post('/rooms', 'RoomController:store');
$app->put('/rooms/{id}', 'RoomController:update');
$app->delete('/rooms/{id}', 'RoomController:delete');

$app->get('/room-types', 'RoomTypeController:getAll');
$app->get('/room-types/{id}', 'RoomTypeController:getById');
$app->post('/room-types', 'RoomTypeController:store');
$app->put('/room-types/{id}', 'RoomTypeController:update');
$app->delete('/room-types/{id}', 'RoomTypeController:delete');

$app->get('/room-groups', 'RoomGroupController:getAll');
$app->get('/room-groups/{id}', 'RoomGroupController:getById');
$app->post('/room-groups', 'RoomGroupController:store');
$app->put('/room-groups/{id}', 'RoomGroupController:update');
$app->delete('/room-groups/{id}', 'RoomGroupController:delete');

$app->get('/buildings', 'BuildingController:getAll');
$app->get('/buildings/{id}', 'BuildingController:getById');
$app->post('/buildings', 'BuildingController:store');
$app->put('/buildings/{id}', 'BuildingController:update');
$app->delete('/buildings/{id}', 'BuildingController:delete');

$app->get('/amenities', 'AmenityController:getAll');
$app->get('/amenities/{id}', 'AmenityController:getById');
$app->post('/amenities', 'AmenityController:store');
$app->put('/amenities/{id}', 'AmenityController:update');
$app->delete('/amenities/{id}', 'AmenityController:delete');

$app->get('/bookings', 'BookingController:getAll');
$app->get('/bookings/{id}', 'BookingController:getById');
$app->get('/bookings/last/order-no', 'BookingController:generateOrderNo');
$app->post('/bookings', 'BookingController:store');
$app->put('/bookings/{id}', 'BookingController:update');
$app->delete('/bookings/{id}', 'BookingController:delete');

/** Routes to person db */
$app->get('/depts', 'DeptController:getAll');
$app->get('/depts/{id}', 'DeptController:getById');

$app->get('/staffs', 'StaffController:getAll');
$app->get('/staffs/{id}', 'StaffController:getById');

/** Routes to hosxp db */
$app->get('/ips', 'IpController:getAll');
$app->get('/ips/{an}', 'IpController:getById');

$app->get('/wards', 'WardController:getAll');
$app->get('/wards/{ward}', 'WardController:getById');

$app->group('/api', function(Slim\App $app) { 
    $app->get('/users', 'UserController:index');
    $app->get('/users/{loginname}', 'UserController:getUser');
});

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
