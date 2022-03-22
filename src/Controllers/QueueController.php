<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Booking;
use App\Models\BookingCheckin;
use App\Models\Room;
use App\Models\Ip;

class QueueController extends Controller
{
    public function getAll($request, $response, $args)
    {
        $page = (int)$request->getQueryParam('page');
        $depart = $request->getQueryParam('depart');

        /** ======== พระภิกษุสงฆ์ Filtered ======== */
        $ip = Ip::join('patient', 'ipt.hn', '=','patient.hn')
                    ->where('patient.pname', 'like', 'พระ%')
                    ->pluck('ipt.an');
        /** ======== พระภิกษุสงฆ์ Filtered ======== */

        $model = Booking::with('patient','patient.admit','patient.admit.ward','checkin','user')
                    ->when(!empty($depart), function($q) use ($depart, $ip) {
                        if($depart === '1') { //อายุรกรรม หรือ อื่นๆ
                            $q->whereIn('specialist', ['3','8'])->where('is_officer', '<>', '1');
                        } else if($depart === '2') { //ศัลยกรรม
                            $q->whereIn('specialist', ['2'])->where('is_officer', '<>', '1');
                        } else if($depart === '3') { //ออร์โธปิดิกส์
                            $q->whereIn('specialist', ['5'])->where('is_officer', '<>', '1');
                        } else if($depart === '4') { //สูติ-นรีเวชกรรม
                            $q->whereIn('specialist', ['1'])->where('is_officer', '<>', '1');
                        } else if($depart === '5') { //กุมารเวชกรรม
                            $q->whereIn('specialist', ['4'])->where('is_officer', '<>', '1');
                        } else if($depart === '6') { //จักษุ
                            $q->whereIn('specialist', ['6'])->where('is_officer', '<>', '1');
                        } else if($depart === '7') { //โสต ศอ นาสิก
                            $q->whereIn('specialist', ['7'])->where('is_officer', '<>', '1');
                        } else if($depart === '8') { //พระภิกษุสงฆ์
                            $q->whereIn('an', $ip)->where('is_officer', '<>', '1');
                        } else if($depart === '9') { //บุคลากร รพ.
                            $q->where('is_officer', '1');
                        }
                    })
                    ->where('book_status', '=', 0)
                    ->orderBy('book_date');

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
            if(BookingCheckin::where(['book_id' => $args['id'], 'room_id' => $args['roomId']])->delete()) {
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
}
