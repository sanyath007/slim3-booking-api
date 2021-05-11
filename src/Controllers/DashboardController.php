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
    
    public function bookings($req, $res, $args)
    {
        $sdate = $args['month']. '-01';
        $edate = $args['month']. '-31';

        $sql="SELECT CAST(DAY(vstdate) AS SIGNED) AS d,
            COUNT(DISTINCT vn) as num_pt
            FROM ovst
            WHERE (vstdate BETWEEN ? AND ?)
            GROUP BY CAST(DAY(vstdate) AS SIGNED) 
            ORDER BY CAST(DAY(vstdate) AS SIGNED) ";

        return $res->withJson(
            DB::select($sql, [$sdate, $edate])
        );
    }
}
