<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\Campaign;
use App\Http\Requests\DashboardStatisticsRequest;
use App\Http\Resources\DashboardStatisticsResource;
use App\Services\DashboardService;
use Auth;

class DashboardController extends Controller
{
    /**
     * @var DashboardService
     */
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard statistics by date range
     *
     * @param  App\Http\Requests\DashboardStatisticsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getStatistics(DashboardStatisticsRequest $request)
    {
        $user = Auth::user();
        $data = $request->all();
        $result = $this->dashboardService->getStatistics($data['start_date'], $data['end_date'], $user->email);
        return $result;
    }
}
