<?php

namespace App\Http\Controllers;

use App\Http\Models\AQI;
use Carbon\Carbon;
use DateTime;
use Laravel\Lumen\Routing\Controller as BaseController;
use Validator;
use Illuminate\Http\Request;
use MongoDate;

/**
 * @OA\Info(
 *     version='1.0',
 *     title='Sample API Documentation'
 * )
 */
class Controller extends BaseController
{
    /**
     * @OA\Get(
     *     path='/aqis',
     *     summary='Finds Pets by status',
     *     description='Multiple status values can be provided with comma separated string',
     *     operationId='findPetsByStatus',
     *     deprecated=true,
     *     @OA\Parameter(
     *         name='status',
     *         in='query',
     *         description='Status values that needed to be considered for filter',
     *         required=true,
     *         explode=true,
     *         @OA\Schema(
     *             default='available',
     *             type='string',
     *             enum = {'available', 'pending', 'sold'},
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description='successful operation',
     *         @OA\JsonContent(
     *             type='array',
     *         ),
     *         @OA\XmlContent(
     *             type='array',
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description='Invalid status value'
     *     ),
     * )
     */

    public function get()
    {
        $aqi = AQI::raw(function ($collection) {
            return $collection->aggregate([['$group' => ['_id' => '$sensorid']]]);
        });
        return $aqi;
    }

    public function getSensorId()
    {
        $aqi = AQI::raw(function ($collection) {
            return $collection->aggregate([['$group' => ['_id' => '$sensorid']], ['$addFields' => ['id' => '$_id']], ['$project' => ['_id' => 0]]]);
        });
        return $aqi;
    }

    public function getSensor()
    {
        $aqi = AQI::raw(function ($collection) {
            return $collection->aggregate([
                ['$sort' => ['timestamp' => -1]],
                [
                    '$group' =>
                    [
                        '_id' => '$sensorid',
                        'count' => ['$sum' => 1],
                        'data' => ['$first' => '$$ROOT'],
                    ]
                ],
                [
                    '$addFields' => [
                        'data.timestamp' => [
                            '$dateToString' => [
                                'date' => '$data.timestamp',
                                'format' => '%Y-%m-%d %H:00'
                            ]
                        ],
                    ]
                ],
                [
                    '$project' =>
                    [
                        'count' => 1,
                        'data.pm01' => 1,
                        'data.pm02' => 1,
                        'data.pm10' => 1,
                        'data.rco2' => 1,
                        'data.atmp' => 1,
                        'data.rhum' => 1,
                        'data.wifi' => 1,
                        'data.tvoc' => 1,
                        'data.timestamp' => 1,
                    ]
                ]
            ]);
        });
        return $aqi;
    }

    public function getSensorSort(Request $request)
    {

        $sortKey = $request->get('sort', 'pm01');
        $aqi = AQI::raw(function ($collection) use ($sortKey) {
            return $collection->aggregate([
                ['$sort' => ['timestamp' => -1]],
                [
                    '$group' =>
                    [
                        '_id' => '$sensorid',
                        'count' => ['$sum' => 1],
                        'data' => ['$first' => '$$ROOT'],
                    ]
                ],
                [
                    '$addFields' => [
                        'data.timestamp' => [
                            '$toString' => '$data.timestamp'
                        ],
                    ]
                ],
                [
                    '$project' =>
                    [
                        'count' => 1,
                        'data.pm01' => 1,
                        'data.pm02' => 1,
                        'data.pm10' => 1,
                        'data.rco2' => 1,
                        'data.atmp' => 1,
                        'data.rhum' => 1,
                        'data.wifi' => 1,
                        'data.tvoc' => 1,
                        'data.timestamp' => 1,
                    ]
                ],
                [
                    '$sort' => [
                        'data.' . $sortKey => -1
                    ]
                ]
            ]);
        });
        return $aqi;
    }

    // public function report(Request $request)
    // {

    //     \DB::connection('mongodb')->enableQueryLog();
    //     $validator = Validator::make($request->all(), [
    //         'start_at' => 'required',
    //         'end_at' => 'required',
    //         'sensordid' => 'required',
    //         'type' => 'required|in:hour,day,month',
    //     ]);

    //     if ($validator->fails()) {
    //         return response($validator->messages());
    //     }
    //     // dd($request->all());

    //     // $start = Carbon::createFromDate(2022, 02, 17);

    //     // $data = AQI::where('timestamp', '>=', new DateTime($request->start_at))
    //     //     ->where('sensorid', $request->sensordid)->get();

    //     // dd(\DB::connection('mongodb')->getQueryLog(), $data);

    //     // return $data;

    //     $aqi = AQI::raw(function ($collection) use ($request) {

    //         $formatType = ['hour' => '%Y%m%d%H', 'day' => '%Y%m%d', 'month' => '%Y%m'];

    //         // $startPhpDate = new DateTime('now');
    //         // $endPhpDate = new DateTime('2022-03-25 23:59:00.0');
    //         // $start = new \MongoDB\BSON\UTCDateTime($startPhpDate->getTimestamp());
    //         // $end = new \MongoDB\BSON\UTCDateTime($endPhpDate->getTimestamp());
    //         // dd(date(DATE_ISO8601, strtotime('2010-12-30 23:21:46')));
    //         // dd($start->toDateTime(), $end->toDateTime(), $startPhpDate);

    //         // $start = date(DATE_ISO8601, strtotime('2022-02-17 00:00:00'));
    //         // $end = date(DATE_ISO8601, strtotime('2022-03-25 23:59:00'));
    //         // $start = new DateTime('2022-02-17 00:00:00');
    //         $start = Carbon::createFromDate(2022, 02, 17);
    //         // $end = new DateTime('2022-03-25 23:59:00');
    //         // dd($start, $end);
    //         return $collection->aggregate([
    //             ['$match' =>
    //             [
    //                 // 'sensorid' => ['$in' => [$request->sensordid]],
    //                 'timestamp' => ['$gte' => ['$date' => ['$numberLong' => "1648166400000"]]],
    //                 // '$and' => [
    //                 //     ['timestamp' => ['$gte' => $start]],
    //                 //     ['timestamp' => ['$lt' => $end]]
    //                 // ]
    //             ]]
    //             // [
    //             //     '$group' =>
    //             //     [
    //             //         '_id' => ['$dateToString' => ['format' => $formatType[$request->type], 'date' => '$timestamp']],
    //             //         'count' => ['$sum' => 1],
    //             //         'pm01' => ['$avg' =>  '$pm01'],
    //             //         'pm02' => ['$avg' =>  '$pm02'],
    //             //         'pm10' => ['$avg' =>  '$pm10'],
    //             //         'rco2' => ['$avg' =>  '$rco2'],
    //             //         'atmp' => ['$avg' =>  '$atmp'],
    //             //         'rhum' => ['$avg' =>  '$rhum'],
    //             //         'wifi' => ['$avg' =>  '$wifi'],
    //             //         'tvoc' => ['$avg' =>  '$tvoc'],
    //             //         'timestamp' => ['$first' => '$timestamp']
    //             //     ]
    //             // ],
    //             // [
    //             //     '$addFields' => [
    //             //         'data.timestamp' => [
    //             //             '$toString' => '$data.timestamp'
    //             //         ],
    //             //     ]
    //             // ],
    //         ]);
    //     });
    //     dd(\DB::connection('mongodb')->getQueryLog(), $aqi);
    //     return $aqi;
    // }
}
