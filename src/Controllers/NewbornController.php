<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Newborn;
use App\Models\Ip;
use App\Models\Patient;
use App\Models\Booking;

class NewbornController extends Controller
{
    public function getAll($request, $response, $args)
    {
        $conditions = [];
        $page = (int)$request->getQueryParam('page');
        $searchStr = $request->getQueryParam('search');

        // TODO: separate search section to another method
        /** ======== Search by patient data section ======== */
        if(!empty($searchStr)) {
            $searches = explode(':', $searchStr);

            if(count($searches) > 0) {
                if($searches[0] == 'an') {
                    $fdName = 'ipt.'.$searches[0];

                    array_push($conditions, [$fdName, '=', $searches[1]]);
                } else if ($searches[0] == 'hn') {
                    $fdName = 'patient.'.$searches[0];

                    array_push($conditions, [$fdName, '=', $searches[1]]);
                } else {
                    list($fname, $lname) = explode(',', $searches[1]);

                    array_push($conditions, ['patient.fname', 'like', $fname.'%']);
                    array_push($conditions, ['patient.lname', 'like', $lname.'%']);
                }
            }
        }
        /** ======== Search by patient data section ======== */

        $model = Newborn::with('ip', 'ip.patient')
                    ->join('ipt', 'ipt.an', '=','ipt_newborn.an')
                    ->join('patient', 'ipt.hn', '=','patient.hn')
                    ->whereNull('ipt.dchdate')
                    ->when(count($conditions) > 0, function($q) use ($conditions) {
                        $q->where($conditions);
                    })
                    ->orderBy('regdate');

        $bookings = paginate($model, 10, $page, $request);

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($bookings, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE)); 
    }
    
    public function getById($request, $response, $args)
    {
        $ip = Ip::where('an', $args['an'])
                ->with('patient')
                ->with('ward:ward,name')
                ->with('pttype:pttype,name')
                ->with('patient.address')
                ->with('admdoctor:code,name,licenseno')
                ->first();

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($ip, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }
}
