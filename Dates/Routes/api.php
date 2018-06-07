<?php
/**
 * Created by Melnik Sergey, Appus Studio on 05.01.18.
 */

Route::group([
    'middleware' => 'auth:api'
], function () {
    Route::get('days/public', 'DateController@getPublicDates');
    Route::get('days/available', 'DateController@getAvailableDate');
    Route::get('days/me', 'DateController@getAvailableDateForMe');
    Route::post('days/handle', 'DateController@saveMyDays');
});