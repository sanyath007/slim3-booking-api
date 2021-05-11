<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController extends Controller
{
    public function overallBookings($req, $res, $args)
    {
        $sql="SELECT
                COUNT(book_id) as book_all,
                COUNT(case when (book_status='0') then book_id end) as book_queue,
                COUNT(case when (book_status='1') then book_id end) as book_stay
                FROM bookings ";

        return $res->withJson(collect(DB::select($sql))->first());
    }
    
    public function overallRooms($req, $res, $args)
    {
        $sql="SELECT
                COUNT(room_id) as all_room,
                COUNT(case when (room_status='0') then room_id end) as room_empty,
                COUNT(case when (room_status='1') then room_id end) as room_used
                FROM rooms
                WHERE (room_status IN (0, 1))";

        return $res->withJson(collect(DB::select($sql))->first());
    }
    
    public function bookingsByRoomtype($req, $res, $args)
    {
        $sdate = $args['month']. '-01';
        $edate = $args['month']. '-31';

        $sql="SELECT
                COUNT(r.room_id) as total,
                COUNT(case when (r.room_type='1') then r.room_id end) as std,
                COUNT(case when (r.room_type='2') then r.room_id end) as vip,
                COUNT(case when (r.room_type='3') then r.room_id end) as vvip
                FROM booking_rooms br 
                left join rooms r on (br.room_id=r.room_id)
                WHERE (br.checkin_date BETWEEN ? AND ?) ";

        return $res->withJson(collect(DB::select($sql, [$sdate, $edate]))->first());
    }
}
