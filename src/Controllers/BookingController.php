<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\Patient;
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

        try {
            // TODO: separate search section to another method
            /** ======== Search by patient data section ======== */
            $patientList = [];
            if(!empty($searchStr)) {
                $conditions = [];
                $searches = explode(':', $searchStr);
                if(count($searches) > 0) {
                    if ($searches[0] == 'fname') {
                        array_push($conditions, ['patient.'.$searches[0], 'like', '%'.$searches[1].'%']);
                    } else if ($searches[0] == 'an') {
                        array_push($conditions, ['ipt.'.$searches[0], '=', $searches[1]]);
                    } else {
                        array_push($conditions, ['patient.'.$searches[0], '=', $searches[1]]);
                    }
                }

                $patientList = Patient::leftJoin('ipt', 'ipt.hn', '=','patient.hn')
                                ->where($conditions)
                                ->pluck('patient.hn');
            }
            /** ======== Search by patient data section ======== */

            $model = Booking::with('patient','patient.admit','patient.admit.ward','room','user')
                        ->when(!empty($searchStr) ,function($q) use ($patientList) {
                            $q->whereIn('hn', $patientList)->select();
                        })
                        ->where('book_status', '=', 0)
                        ->orderBy('book_date', 'DESC');

            $bookings = paginate($model, 10, $page, $request);

            return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($bookings, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
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
    
    public function getById($request, $response, $args)
    {
        $booking = Booking::where('book_id', $args['id'])
                            ->with('patient','patient.admit','patient.admit.ward','room','user')
                            ->with('patient.admit.pttype','patient.admit.admdoctor','patient.address')
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

        $model = Booking::where('hn', $args['hn'])
                    ->where('book_id', '<>', $args['id'])
                    ->with('patient','patient.admit','patient.admit.ward','room','user')
                    ->with('patient.admit.pttype','patient.admit.admdoctor','patient.address');

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

            /** If existed patient in booking data */
            $existed = Booking::where('hn', $post['hn'])->whereIn('book_status', [0,1])->first();

            if ($existed == null) {
                $booking = new Booking;
                $booking->an            = $post['an'];
                $booking->hn            = $post['hn'];
                $booking->book_date     = $post['book_date'];
                $booking->book_name     = $post['book_name'];
                $booking->book_tel      = $post['book_tel'];
                $booking->ward          = $post['ward'];
                $booking->specialist    = $post['specialist'];
                $booking->room_types    = $post['room_types'];
                $booking->is_labour     = $post['is_labour'];
                $booking->baby          = $post['baby'];
                $booking->is_officer    = $post['is_officer'];
                $booking->description   = $post['description'];
                $booking->remark        = $post['remark'];
                $booking->created_by    = $post['user'];
                $booking->updated_by    = $post['user'];
                $booking->book_status   = 0;

                if($booking->save()) {
                    return $response
                            ->withStatus(201)
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
            } else {
                return $response
                            ->withStatus(409)
                            ->withHeader("Content-Type", "application/json")
                            ->write(json_encode([
                                'status' => 2,
                                'message' => 'Patient have had a booking!!'
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
            // $booking->an            = $post['an']; // ไม่ให้แก้ไขผู้ป่วย
            $booking->book_date     = $post['book_date'];
            $booking->book_name     = $post['book_name'];
            $booking->book_tel      = $post['book_tel'];
            $booking->ward          = $post['ward'];
            $booking->specialist    = $post['specialist'];
            $booking->room_types    = $post['room_types'];
            $booking->is_labour     = $post['is_labour'];
            $booking->baby          = $post['baby'];
            $booking->is_officer    = $post['is_officer'];
            $booking->description   = $post['description'];
            $booking->remark        = $post['remark'];
            $booking->updated_by    = $post['user'];
            $booking->book_status   = 0;

            if($booking->save()) {
                return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status'    => 1,
                            'message'   => 'Updating successfully',
                            'booking'   => $booking
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
            $post = (array)$request->getParsedBody();

            if(Booking::where('book_id', $args['id'])->update([
                'book_status'   => 9,
                'updated_by'    => $post['user']
            ])) {
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
            $post = (array)$request->getParsedBody();

            if(Booking::where('book_id', $args['id'])->update([
                'book_status'   => 3,
                'updated_by'    => $post['user']
            ])) {
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
            /** ตรวจสอบว่าผู้ป่วยถูกรับเข้าห้องหรือยัง */
            $isCheckedIn = BookingRoom::where('book_id', $args['id'])->count();

            // TODO: ถ้าผู้ป่วยถูกรับเข้าห้องแล้วให้ response กลับพร้อม message แจ้ง
            if ($isCheckedIn == 0) {
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
            } else {
                return $response
                    ->withStatus(409)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'It\'s have checked in!!'
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
            $post = (array)$request->getParsedBody();

            if(BookingRoom::where(['book_id' => $args['id'], 'room_id' => $args['roomId']])->delete()) {
                Booking::where('book_id', $args['id'])->update([
                    'book_status'   => 0,
                    'updated_by'    => $post['user']
                ]);
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
            $br->book_id        = $post['bookId'];
            $br->room_id        = $post['roomId'];
            $br->checkin_date   = $post['checkinDate'];
            $br->checkin_time   = $post['checkinTime'];
            $br->have_observer  = $post['haveObserver'];
            $br->observer_name  = $post['observerName'];
            $br->observer_name  = $post['observerTel'];
            $br->created_by     = $post['user'];
            $br->updated_by     = $post['user'];

            if ($br->save()) {
                Booking::where('book_id', $post['bookId'])->update([
                    'book_status'   => 1,
                    'updated_by'    => $post['user']
                ]);
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
            $post = (array)$request->getParsedBody();

            $br = BookingRoom::where('book_id', $args['id'])
                    ->where('room_id', $args['roomId'])
                    ->update([
                        'checkout_date' => date('Y-m-d'),
                        'checkout_time' => date('H:i:s'),
                        'updated_by'    => $post['user']
                    ]);

            if ($br) {
                Booking::where('book_id', $args['id'])->update([
                    'book_status'   => 2,
                    'updated_by'    => $post['user']
                ]);
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

    public function changRoom($request, $response, $args)
    {
        try {
            $post = (array)$request->getParsedBody();

            $br = BookingRoom::where('book_id', $args['id'])->first();
            /** Retrieve old room id  */
            $oldRoom = $br->room_id;

            $br->room_id    = $post['new_room'];
            $br->updated_by = $post['user'];

            if ($br->save()) {
                /** Update status of old room */
                Room::where('room_id', $oldRoom)->update(['room_status' => 0]);

                /** Update status of new room */
                Room::where('room_id', $post['new_room'])->update(['room_status' => 1]);

                /** TODO: To store changing room data to specific table */

                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 1,
                        'message' => 'Changing room successfully',
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
