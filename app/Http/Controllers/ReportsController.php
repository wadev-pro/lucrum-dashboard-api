<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\Campaign;
use App\Http\Requests\{LeadReportRequest, RouteReportRequest};
use App\Services\{ReportsService, ReportStatusService};
use App\Jobs\LeadReport;
use App\Enums\{ReportType, ReportStatusType};
use Auth;

class ReportsController extends Controller
{
    /**
     * @var ReportsService
     */
    protected $reportsService;
    /**
     * @var ReportStatusService
     */
    protected $reportStatusService;

    public function __construct(
        ReportsService $reportsService,
        ReportStatusService $reportStatusService
    )
    {
        $this->reportsService = $reportsService;
        $this->reportStatusService = $reportStatusService;
    }

    /**
     * Get lead report by date range
     *
     * @param  App\Http\Requests\LeadReportRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getLeadReport(LeadReportRequest $request)
    {
        $user_id = $request->user_id;
        $filter = $request->except(['user_id']);
        $result = $this->reportsService->getLeadReports($filter, $user_id);
        return $result;
    }

    /**
     * Get route report by date range
     *
     * @param  App\Http\Requests\RouteReportRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getRouteReport(RouteReportRequest $request)
    {
        $user_id = $request->user_id;
        $filter = $request->except(['user_id']);
        $result = $this->reportsService->getRouteReports($filter, $user_id);
        return $result;
    }

    /**
     * Get lead report export by filter
     *
     * @param  App\Http\Requests\LeadReportRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getLeadReportExport(LeadReportRequest $request)
    {
        $user_id = $request->user_id;
        $filters = $request->except(['user_id']);
        $report_status = $this->reportStatusService->createReportStatus(ReportType::LeadReport, $user_id, $filters, ReportStatusType::InProgress);
        // LeadReport::dispatchNow($filters, $user_id, $report_status);
        LeadReport::dispatch($filters, $user_id, $report_status)->onQueue(ReportType::LeadReport);
        return response()->json([
            'result' => 'success'
        ]);
    }
}
