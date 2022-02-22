<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Models\Room;
use App\Models\RoomAmenities;

class RoomController extends Controller
{
    public function getAll($request, $response, $args)
    {
        $page = (int)$request->getQueryParam('page');

        if ($page) {
            $data = paginate(Room::with('roomType', 'roomGroup', 'building')->orderBy('room_no'), 10, $page, $request);
        } else {
            $data = [
                'items' => Room::with('roomType', 'roomGroup', 'building')->orderBy('room_no')->get()
            ];
        }

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }

    public function getById($request, $response, $args)
    {
        $room = Room::with('amenities', 'amenities.amenity')
                    ->where('room_id', $args['id'])
                    ->first();
                    
        $data = json_encode($room, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE);

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write($data);
    }

    public function getByBuilding($request, $response, $args)
    {
        $rooms = Room::where(['building' => $args['id'], 'room_status' => 0])
                    ->orderBy('room_no')
                    ->get();
                    
        $data = json_encode($rooms, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE);

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write($data);
    }
    
    public function getRoomsStatus($request, $response, $args)
    {
        $rooms = Room::whereNotIn('room_status', [2,3])
                    ->orderBy('room_no')
                    ->get();
        $usedRooms = Room::where(['room_status' => 1])
                    ->with('bookingRoom', 'bookingRoom.booking')
                    ->with('bookingRoom.booking.patient', 'bookingRoom.booking.patient.admit')
                    ->orderBy('room_no')
                    ->get();

        $data = json_encode([
            'rooms' => $rooms, 
            'usedRooms' => $usedRooms
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE);

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write($data);
    }

    private function uploadImage($img, $img_url)
    {
        $regx = "/^data:image\/(?<extension>(?:png|gif|jpg|jpeg));base64,(?<image>.+)$/";

        if(preg_match($regx, $img, $matchings)) {
            $img_data = file_get_contents($img);
            $extension = $matchings['extension'];
            $img_name = uniqid().'.'.$extension;
            $img_full_url = str_replace('/index.php', '/assets/uploads/'.$img_name, $img_url);
            $file_to_upload = 'assets/uploads/'.$img_name;

            if(file_put_contents($file_to_upload, $img_data)) {
                return $img_full_url;
            }
        }

        return '';
    }

    public function store($request, $response, $args)
    {
        $upload_url = 'http://'.$request->getServerParam('SERVER_NAME').$request->getServerParam('PHP_SELF');

        try {
            $post = (array)$request->getParsedBody();

            /** Upload image */
            $img_url = $this->uploadImage($post['room_img_url'], $upload_url);

            $room = new Room;
            $room->room_no = $post['room_no'];
            $room->room_name = $post['room_name'];
            $room->description = $post['description'];
            $room->room_type = $post['room_type'];
            $room->room_group = $post['room_group'];
            $room->building = $post['building'];
            $room->floor = $post['floor'];
            $room->room_img_url = $img_url;
            $room->room_status = 0;

            if($room->save()) {
                $newRoomId = $room->room_id;
                $amenities = explode(",", $post['amenities']);

                foreach($amenities as $amenity) {
                    $ra = new RoomAmenities();
                    $ra->room_id = $newRoomId;
                    $ra->amenity_id = $amenity;
                    $ra->status = 1;
                    $ra->save();
                }

                return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Insertion successfully!!',
                            'room' => $room
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            } // end if
        } catch (\Throwable $th) {
            /** Delete new room if error occurs */
            // Room::find($newRoomId)->delete();
            
            /** And set data to client with http status 500 */
            return $response->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        } // end trycatch
    }

    public function update($request, $response, $args)
    {
        $upload_url = 'http://'.$request->getServerParam('SERVER_NAME').$request->getServerParam('PHP_SELF');

        try {
            $post = (array)$request->getParsedBody();

            $room = Room::where('room_id', $args['id'])->first();
            $room->room_no = $post['room_no'];
            $room->room_name = $post['room_name'];
            $room->description = $post['description'];
            $room->room_type = $post['room_type'];
            $room->room_group = $post['room_group'];
            $room->building = $post['building'];
            $room->floor = $post['floor'];
            // $room->room_status = 0;

            /** Upload image */
            $img_url = $this->uploadImage($post['room_img_url'], $upload_url);

            /** If room_img_url in db is empty and user upload file do this */
            if(empty($room->room_img_url) && !empty($img_url)) {
                $room->room_img_url = $img_url;
            }

            if($room->save()) {
                $newRoomId = $room->room_id;
                
                if(count($post['amenities']) > 0) {
                    RoomAmenities::where('room_id', $newRoomId)->delete();

                    foreach($post['amenities'] as $amenity) {
                        $ra = new RoomAmenities;
                        $ra->room_id = $newRoomId;
                        $ra->amenity_id = $amenity;
                        $ra->status = 1;
                        $ra->save();
                    }
                }

                return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Update successfully!!',
                            'room' => Room::with('roomType', 'roomGroup', 'building')
                                        ->where('room_id', $args['id'])
                                        ->first()
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $th) {
            /** Delete new room if error occurs */
            // Room::find($newRoomId)->delete();
            
            /** And set data to client with http status 500 */
            return $response->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        } // end trycatch
    }
    
    public function updateStatus($request, $response, $args)
    {
        try {
            if(Room::where('room_id', $args['id'])->update(['room_status' => $args['status']])) {
                return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode([
                            'status' => 1,
                            'message' => 'Update successfully!!',
                            'room' => Room::with('roomType', 'roomGroup', 'building')
                                        ->where('room_id', $args['id'])
                                        ->first()
                        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $th) {
            /** And set data to client with http status 500 */
            return $response->withStatus(500)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode([
                        'status' => 0,
                        'message' => 'Something went wrong!!'
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        } // end trycatch
    }

    public function delete($request, $response, $args)
    {
        $room = Room::where('room_id', $args['id'])->first();
        
        if($room->delete()) {
            return $response->withStatus(200)
                    ->withHeader("Content-Type", "application/json")
                    ->write(json_encode($room, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
        }
    }
}
