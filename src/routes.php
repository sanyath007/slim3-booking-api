<?php

$app->options('/{routes:.+}', function($request, $response, $args) {
    return $response;
});

$app->get('/', 'HomeController:home')->setName('home');

$app->post('/login', 'LoginController:login')->setName('login');

$app->get('/dashboard/bookings', 'DashboardController:overallBookings');
$app->get('/dashboard/rooms', 'DashboardController:overallRooms');
$app->get('/dashboard/{month}/bookings-by-roomtype', 'DashboardController:bookingsByRoomtype');

$app->get('/rooms', 'RoomController:getAll');
$app->get('/rooms/{id}', 'RoomController:getById');
$app->get('/rooms/building/{id}', 'RoomController:getByBuilding');
$app->post('/rooms', 'RoomController:store');
$app->put('/rooms/{id}', 'RoomController:update');
$app->delete('/rooms/{id}', 'RoomController:delete');
$app->get('/rooms-status', 'RoomController:getRoomsStatus');

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
$app->get('/bookings/an/{an}', 'BookingController:getByAn');
$app->get('/bookings/last/order-no', 'BookingController:generateOrderNo');
$app->get('/bookings/{id}/{an}/histories', 'BookingController:histories');
$app->post('/bookings', 'BookingController:store');
$app->put('/bookings/{id}', 'BookingController:update');
$app->put('/bookings/{id}/cancel', 'BookingController:cancel');
$app->put('/bookings/{id}/discharge', 'BookingController:discharge');
$app->delete('/bookings/{id}', 'BookingController:delete');
$app->post('/bookings/checkin', 'BookingController:checkin');
$app->put('/bookings/{id}/{roomId}/checkout', 'BookingController:checkout');
$app->put('/bookings/{id}/{roomId}/cancel-checkin', 'BookingController:cancelCheckin');

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

$app->get('/patients', 'PatientController:getAll');
$app->get('/patients/{hn}', 'PatientController:getById');

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
