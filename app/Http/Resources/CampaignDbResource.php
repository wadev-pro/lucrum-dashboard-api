<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignDbResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "name" => $this['name'],
            "createdAt" => $this['created_at'],
            "createdBy" => $this['created_by'],
            "lastUpdateBy" => $this['last_update_by'],
            "lastUpdateAt" => $this['last_update_at'],
            "userId" => '',
            "messageTemplateGroupId" => '',
            "didPoolId" => '',
            "callImpressionUrl" => $this['call_impression_url'],
            "destinationUrl" => $this['destination_url'],
            "fileUrl" => $this['file_name'],
            "cronExpression" => $this['cron_expression'],
            "scheduleTime" => $this['schedule_time'],
            "disableCarrierCheck" => false,
            "injectTestMessageEvery" => null,
            "deliveryInterval" => $this['delivery_interval'],
            "routingPlanId" => '',
            "maxNumberOfSendMessagesForNoneClickers" => null,
            "subDomainLength" => null,
            "processingStatus" => $this['processing_status'],
            "validationCompletedAt" => $this['validated_at'],
            "validationStartedAt" => '',
            "validationFailureReason" => '',
            "lastProcessingStartRequestedAt" => '',
            "lastProcessingStartedAt" => '',
            "lastProcessingStartRequestedBy" => '',
            "lastProcessingStartedBy" => '',
            "lastProcessingStartedFromSequenceNumber" => null,
            "lastProcessingRunAt" => $this['last_run_at'],
            "lastProcessingException" => '',
            "lastProcessingStopRequestedBy" => '',
            "lastProcessingStopRequestedAt" => '',
            "lastProcessingStopException" => '',
            "lastProcessingStoppedAt" => '',
            "lastProcessingStoppedBy" => '',
            "lastMessageCraftingStartedAt" => '',
            "lastMessageCraftingFinishedAt" => '',
            "autoStart" => true,
            "initialProcessingBatchSize" => null,
            "isEmpty" => true,
            "campaignSequenceNumber" => null,
            "composerJobId" => '',
            "validationJobId" => '',
            "requestedProcessingBatchSize" => null,
            "totalCampaignFileMessageCount" => null,
            "totalSeedMessagesGenerated" => null,
            "totalMessageCount" => null,
            "totalBatchesAttemptedCount" => null,
            "totalBatchesSuccessfullyCompletedCount" => null,
            "badRecordsFoundCount" => null,
            "vertical" => '',
            "fileTags" => null,
            "qualityCheckConcurrencyLimit" => null,
            "campaignRunnerConcurrencyLimit" => null,
            "conversationId" => '',
            "qualityCheckStartedAt" => '',
            "qualityCheckCompletedAt" => '',
            "qualityCheckFailedAt" => '',
            "qualityCheckFailureReason" => '',
            "qualityCheckJobId" => '',
            "qualityCheckTotalElapsedTime" => '',
            "qualityCheckRequestMinProcessingTime" => '',
            "qualityCheckRequestMaxProcessingTime" => '',
            "totalFreshLeadsInFile" => null,
            "totalLeadsAlreadyExistingInSystem" => null,
            "totalLeadsBelongingToLandlineInFile" => null,
            "totalLeadsWithValidPhoneNumberInFile" => null,
            "totalLeadsWithInvalidPhoneNumberInFile" => null,
            "totalDuplicateLeadsInFile" => null,
            "totalBlacklistedLeadsInFile" => null,
            "startHasBeenRequestedWhileQualityCheckProcess" => true,
            "totalQualityCheckRequestProcessingErrors" => null,
            "qualityCheckFetchingRecordsFromFileElapsedTime" => '',
            "isQualityCheckReRunRequested" => true,
            "qualityCheckReRunRequestedAt" => '',
            "totalSentMessageCount" => null,
            "totalSendingFailedMessageCount" => null,
            "totalMessageProcessingFailedCount" => null,
            "processingFailedMessagesByException" => null,
            "filteredMessagesByReason" => null,
            "sendingFailedMessagesByReason" => null,
            "maxConcurrentMessagesProcessedCountLastBatch" => null,
            "maxConcurrentMessagesProcessedCountOverall" => 0
        ];
    }
}
