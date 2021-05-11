<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\Ip;

class BookingController extends Controller
{
    public function generateOrderNo($request, $response, $args)
    {
        $bookings = Booking::orderBy('book_id', 'DESC')->first();

        $startId = substr((date('Y') + 543), 2);
        $tmpLastId =  ((int)(substr($bookings->book_id, 4))) + 1;
        $lastId = $startId.sprintf("%'.05d", $tmpLastId);

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($lastId, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }

    public function getAll($request, $response, $args)
    {
        $page = (int)$request->getQueryParam('page');
        $searchStr = $request->getQueryParam('search');

        // TODO: separate search section to another method
        /** ======== Search by patient data section ======== */
        $ip = [];
        if(!empty($searchStr)) {
            $conditions = [];
            $searches = explode(':', $searchStr);
            if(count($searches) > 0) {
                array_push($conditions, [$searches[0], 'like', '%'.$searches[1].'%']);
            }

            $ip = Ip::join('patient', 'ipt.hn', '=','patient.hn')
                    ->where($conditions)
                    ->pluck('ipt.an');
        }
        /** ======== Search by patient data section ======== */

        $model = Booking::with('ip','ip.ward','ip.patient','room','user')
                    ->when(!empty($searchStr) ,function($q) use ($ip) {
                        $q->whereIn('an', $ip);
                    })
                    ->where('book_status', '=', 0)
                    ->orderBy('book_id');

        $bookings = paginate($model, 10, $page, $request);

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($bookings, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }
    
    public function getById($request, $response, $args)
    {
        $booking = Booking::where('book_id', $args['id'])
                            ->with('ip','ip.patient','ip.ward','room','user')
                            ->with('ip.pttype','ip.admdoctor','ip.patient.address')
                            ->first();

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($booking, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }
    
    public function getByAn($request, $response, $args)
    {
        $booking = Booking::where('an', $args['an'])->first();

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($booking, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }
    
    public function histories($request, $response, $args)
    {
        $page = (int)$request->getQueryParam('page');

        $model = Booking::where('an', $args['an'])
                    ->where('book_id', '<>', $args['id'])
                    ->with('ip','ip.patient','ip.ward','room','user')
                    ->with('ip.pttype','ip.admdoctor','ip.patient.address');

        $bookings = paginate($model, 10, $page, $request);

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($bookings, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }

    public function store($request, $response, $args)
    {
        try {
            $post = (array)$request->getParsedBody();

            $booking = new Booking;
            $booking->an = $post['an'];
            $booking->hn = $post['hn'];
            $booking->book_date = $post['book_date'];
            $booking->book_name = $post['book_name'];
            $booking->book_tel = $post['book_tel'];
            $booking->description = $post['description'];
            $booking->remark = $post['remark'];
            $booking->room_types = $post['room_types'];
            $booking->is_officer = $post['is_officer'];
            $booking->user = $post['user'];
            $booking->ward = $post['ward'];
            $booking->book_status = 0;

            if($booking->save()) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Inserting successfully',
                            'booking' => $booking
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function update($request, $response, $args)
    {
        try {
            $post = (array)$request->getParsedBody();

            $booking = Booking::find($args['id']);
            // $booking->an = $post['an']; // ไม่ให้แก้ไขผู้ป่วย
            $booking->book_date = $post['book_date'];
            $booking->book_name = $post['book_name'];
            $booking->book_tel = $post['book_tel'];
            $booking->description = $post['description'];
            $booking->remark = $post['remark'];
            $booking->room_types = $post['room_types'];
            $booking->is_officer = $post['is_officer'];
            $booking->user = $post['user'];
            $booking->ward = $post['ward'];
            $booking->book_status = 0;

            if($booking->save()) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Updating successfully',
                            'booking' => $booking
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function cancel($request, $response, $args)
    {
        try {
            if(Booking::where('book_id', $args['id'])->update(['book_status' => '9'])) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Canceling successfully',
                            'booking' => Booking::where('book_id', $args['id'])->first(),
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function discharge($request, $response, $args)
    {
        try {
            if(Booking::where('book_id', $args['id'])->update(['book_status' => '3'])) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Discharging successfully',
                            'booking' => Booking::where('book_id', $args['id'])->first(),
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function delete($request, $response, $args)
    {
        try {
            if(Booking::where('book_id', $args['id'])->delete()) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Deleting successfully',
                            'booking' => $booking
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function cancelCheckin($request, $response, $args)
    {
        try {
            if(BookingRoom::where(['book_id' => $args['id'], 'room_id' => $args['roomId']])->delete()) {
                Booking::where('book_id', $args['id'])->update(['book_status' => 0]);
                Room::where('room_id', $args['roomId'])->update(['room_status' => 0]);

                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 1,
                        'message' => 'Cancelation checkin successfully'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function checkin($request, $response, $args)
    {
        try {
            $post = (array)$request->getParsedBody();
            
            $br = new BookingRoom();
            $br->book_id = $post['bookId'];
            $br->room_id = $post['roomId'];
            $br->checkin_date = $post['checkinDate'];
            $br->checkin_time = $post['checkinTime'];
            $br->have_observer = $post['haveObserver'];
            $br->observer_name = $post['observerName'];
            $br->observer_name = $post['observerTel'];

            if ($br->save()) {
                Booking::where('book_id', $post['bookId'])->update(['book_status' => 1]);
                Room::where('room_id', $post['roomId'])->update(['room_status' => 1]);

                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 1,
                        'message' => 'Checking in successfully',
                        'data' => $br,
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }

    public function checkout($request, $response, $args)
    {
        try {            
            $br = BookingRoom::where('book_id', $args['id'])
                    ->where('room_id', $args['roomId'])
                    ->update([
                        'checkout_date' => date('Y-m-d'),
                        'checkout_time' => date('H:i:s')
                    ]);

            if ($br) {
                Booking::where('book_id', $args['id'])->update(['book_status' => 2]);
                Room::where('room_id', $args['roomId'])->update(['room_status' => 0]);

                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 1,
                        'message' => 'Checking out successfully',
                        'data' => $br,
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } else {
                return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $ex) {
            return $response
                    ->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => $ex->getMessage()
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }
}
