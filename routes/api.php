<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
    Route::post('emulate', 'AuthController@emulate');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
        Route::put('user', 'AuthController@update')->name('auth.update');
    });
});

Route::group([
  'middleware' => 'auth:api'
], function() {
    Route::prefix('dashboard')->group(function() {
        Route::post('/statistics', 'DashboardController@getStatistics')->name('dashboard.statistics');
    });
    Route::prefix('statistics')->group(function() {
        Route::post('/tld', 'StatisticsController@getTldStatistics')->name('statistics.tld');
        Route::post('/template_group', 'StatisticsController@getTemplateGroupStatistics')->name('statistics.template_group');
    });
    Route::prefix('reports')->group(function() {
        Route::post('/lead', 'ReportsController@getLeadReport')->name('reports.lead');
        Route::post('/lead/export', 'ReportsController@getLeadReportExport')->name('reports.lead_export');
        Route::post('/route', 'ReportsController@getRouteReport')->name('reports.route');
    });

    Route::prefix('report_status')->group(function() {
        Route::get('', 'ReportStatusController@index')->name('report_status.index');
    });

    Route::prefix('campaign')->group(function() {
        Route::post('/', 'CampaignController@index')->name('campaign.index');
        Route::get('/{id}', 'CampaignController@get')->name('campaign.get');
        Route::post('/statistics', 'CampaignController@statistics')->name('campaign.statistics');
        Route::prefix('/{campaign_id}/statistics/')->group(function() {
            Route::get('/message_count', 'CampaignController@getMessageCounts')->name('campaign.message_count');
            Route::get('/message_filtered_count', 'CampaignController@getMessageFilteredCounts')->name('campaign.message_filtered_count');
            Route::post('/carriers', 'CampaignController@getCarrierStatistics')->name('campaign.carrier_statistics');
            Route::post('/dids', 'CampaignController@getDidStatistics')->name('campaign.did_statistics');
            Route::post('/message_template', 'CampaignController@getMessageTemplateStatistics')->name('campaign.message_template_statistics');
            Route::post('/tlds', 'CampaignController@getTldStatistics')->name('campaign.tld_statistics');
            Route::post('/sms_report', 'CampaignController@getSmsStatistics')->name('campaign.sms_statistics');
            Route::post('/click_report', 'CampaignController@getClickStatistics')->name('campaign.click_statistics');
            Route::post('/conversion_report', 'CampaignController@getConversionStatistics')->name('campaign.conversion_statistics');
        });
    });
    Route::prefix('dids')->group(function() {
        Route::get('/', 'DidController@index')->name('dids.index');
    });
    Route::prefix('users')->group(function() {
        Route::get('/', 'UserController@index')->name('users.index');
    });
    Route::prefix('user')->group(function() {
        Route::post('', 'UserController@store')->name('user.store');
        Route::get('/{id}', 'UserController@show')->name('user.show');
        Route::put('/{id}', 'UserController@update')->name('user.update');
        Route::delete('/{id}', 'UserController@destroy')->name('user.delete');
    });
    Route::prefix('file')->group(function() {
        Route::post('/export_token', 'FileController@getExportToken')->name('file.export_token');
    });
});

Route::prefix('file')->group(function() {
    Route::get('/export_report', 'FileController@exportReport')->name('file.export_report');
});
