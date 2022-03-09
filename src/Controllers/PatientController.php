<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Patient;

class PatientController extends Controller
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

        $model = Patient::where('death', '<>', 'Y')
                    ->where('hn', '<>', '0000000')
                    ->when(count($conditions) > 0, function($q) use ($conditions) {
                        $q->where($conditions);
                    })
                    ->orderBy('hn');

        $bookings = paginate($model, 10, $page, $request);

        return $response
                ->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($bookings, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }
    
    public function getById($request, $response, $args)
    {
        $patient = Patient::where('hn', $args['hn'])->first();

        return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($patient, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    }

    // public function store($request, $response, $args)
    // {
    //     $post = (array)$request->getParsedBody();

    //     $Patient = new Patient;
    //     $Patient->name = $post['Patient_name'];
        
    //     if($Patient->save()) {
    //         return $response->withStatus(200)
    //                 ->withHeader("Content-Type", "application/json")
    //                 ->write(json_encode($Patient, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    //     }                    
    // }

    // public function update($request, $response, $args)
    // {
    //     $post = (array)$request->getParsedBody();

    //     $Patient = Patient::where('Patient_id', $args['id'])->first();
    //     $Patient->name = $post['Patient_name'];
        
    //     if($Patient->save()) {
    //         return $response->withStatus(200)
    //                 ->withHeader("Content-Type", "application/json")
    //                 ->write(json_encode($Patient, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    //     }
    // }

    // public function delete($request, $response, $args)
    // {
    //     $Patient = Patient::where('Patient_id', $args['id'])->first();
        
    //     if($Patient->delete()) {    
    //         return $response->withStatus(200)
    //                 ->withHeader("Content-Type", "application/json")
    //                 ->write(json_encode($Patient, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE));
    //     }
    // }
}
