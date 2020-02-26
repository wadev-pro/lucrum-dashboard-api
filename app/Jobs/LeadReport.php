<?php

namespace App\Jobs;

use Excpetion;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Model\FileToken;
use App\Services\ReportsService;
use App\Model\ReportStatus;
use App\Enums\{ReportType, ReportStatusType};

class LeadReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    protected $filters, $user_id, $report_status;
    /**
     * Create a new job instance.
     *
     * @param Array $filters
     * @param String $user_id
     * @param ReportStatus $report_status
     * @return void
     */
    public function __construct($filters, $user_id, $report_status)
    {
        $this->filters = $filters;
        $this->user_id = $user_id;
        $this->report_status = $report_status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ReportsService $reportsService)
    {
        $filters = $this->filters;
        $user_id = $this->user_id;
        $report_status = $this->report_status;
        $reportsService->writeLeadDataToCSV($filters, $user_id, $report_status);
    }

    public function get_report_status(){
        return $this->report_status;
    }

}
