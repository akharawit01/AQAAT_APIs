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

    public function report(Request $request)
    {

        \DB::connection('mongodb')->enableQueryLog();
        $validator = Validator::make($request->all(), [
            'start_at' => 'required',
            'end_at' => 'required',
            'sensordid' => 'required',
            'type' => 'required|in:hour,day,month',
            'skip' => 'required',
            'limit' => 'required',
        ]);

        if ($validator->fails()) {
            return response($validator->messages());
        }

        $aqi = AQI::raw(function ($collection) use ($request) {

            $formatType = ['hour' => '%Y%m%d%H', 'day' => '%Y%m%d', 'month' => '%Y%m'];

            $startDate = Carbon::parse($request->start_at);
            $endDate = Carbon::parse($request->end_at);
            $start = new \MongoDB\BSON\UTCDateTime($startDate->timestamp * 1000);
            $end = new \MongoDB\BSON\UTCDateTime($endDate->timestamp * 1000);

            $_skip = (int)$request->skip;
            $_limit = (int)$request->limit;
            return $collection->aggregate([
                ['$match' =>
                [
                    'sensorid' => ['$in' => [$request->sensordid]],
                    '$and' => [
                        ['timestamp' => ['$gte' => $start]],
                        ['timestamp' => ['$lt' => $end]]
                    ]
                ]],
                [
                    '$group' =>
                    [
                        '_id' => ['$dateToString' => ['format' => $formatType[$request->type], 'date' => '$timestamp']],
                        'count' => ['$sum' => 1],
                        'pm01' => ['$avg' =>  '$pm01'],
                        'pm02' => ['$avg' =>  '$pm02'],
                        'pm10' => ['$avg' =>  '$pm10'],
                        'rco2' => ['$avg' =>  '$rco2'],
                        'atmp' => ['$avg' =>  '$atmp'],
                        'rhum' => ['$avg' =>  '$rhum'],
                        'wifi' => ['$avg' =>  '$wifi'],
                        'tvoc' => ['$avg' =>  '$tvoc'],
                        'timestamp' => ['$first' => '$timestamp']
                    ]
                ],
                [
                    '$facet' => [
                        'metadata' => [[ '$count' => 'total' ]],
                        'data' => [[ '$skip' => $_skip ], [ '$limit' => $_limit ]]
                    ]
                ]
            ]);
        });
        return $aqi;
    }
}
