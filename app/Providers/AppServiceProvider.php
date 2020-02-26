<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use App\Model\{ReportStatus};
use App\Enums\ReportStatusType;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
            $rawJob = json_decode($event->job->getRawBody(), true);
            $job = unserialize($rawJob['data']['command']);
            $report_status = $job->get_report_status();
            $log = $event->exception->getMessage();

            $data = array(
                'status' => ReportStatusType::Failed,
                'completed_at' => Carbon::now(),
                'log' => $log
            );
            ReportStatus::where('id', $report_status->id)
                ->update($data);
        });
    }
}
