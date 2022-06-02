<?php

namespace App\Http\Controllers;

use App\Http\Models\AQI;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="Sample API Documentation"
 * )
 */
class Controller extends BaseController
{
    /**
     * @OA\Get(
     *     path="/aqis",
     *     summary="Finds Pets by status",
     *     description="Multiple status values can be provided with comma separated string",
     *     operationId="findPetsByStatus",
     *     deprecated=true,
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status values that needed to be considered for filter",
     *         required=true,
     *         explode=true,
     *         @OA\Schema(
     *             default="available",
     *             type="string",
     *             enum = {"available", "pending", "sold"},
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *         ),
     *         @OA\XmlContent(
     *             type="array",
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status value"
     *     ),
     * )
     */

    function get()
    {

        $aqi = AQI::take(5)
            ->get();;
        return $aqi;
    }
}
