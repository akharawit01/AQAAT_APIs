<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->group(['prefix' => 'api'], function () use ($router) {

    $router->get('/', function () use ($router) {
        return $router->app->version();
    });


    $router->get('/aqis/ids', 'Controller@getSensorId');
    $router->get('/aqis/sensors', 'Controller@getSensor');
    $router->get('/aqis/sensors/sort', 'Controller@getSensorSort');
    $router->get('/aqis/sensors/sortByDate', 'Controller@getSensorSortByDate');
    $router->get('/aqis/sensors/report', 'Controller@report');
});
