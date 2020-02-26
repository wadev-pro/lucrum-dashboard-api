<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\ReportStatus;
use App\Enums\ReportType;
use App\Services\ReportStatusService;
use App\Http\Resources\ReportStatusResource;
use Auth;

class ReportStatusController extends Controller
{
    /**
     * @var ReportStatusService
     */
    protected $reportStatusService;

    public function __construct(ReportStatusService $reportStatusService)
    {
        $this->reportStatusService = $reportStatusService;
    }

    /**
     * Get list of ReportStatus
     *
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_id = $request->user_id;
        $list = $this->reportStatusService->getList($user_id);
        return ReportStatusResource::collection($list);
    }
}
