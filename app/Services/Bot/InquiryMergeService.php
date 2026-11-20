<?php

namespace App\Services\Bot;

use App\Enums\BotNoticeType;
use App\Models\FollowUpRun;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * "Unir a la consulta de…": the bot opened a second inquiry that turned out to be the same trip.
 */
class InquiryMergeService
{
    /**
     * Moves the data and the messages to the target and deletes the duplicate. What the target already knows wins.
     */
    public function merge(Lead $duplicate, Lead $target): Lead
    {
        if ($duplicate->is($target) || $duplicate->customer_id !== $target->customer_id) {
            throw new RuntimeException('Solo se pueden unir consultas distintas del mismo cliente.');
        }

        if ($duplicate->booking()->exists()) {
            throw new RuntimeException('Esta consulta ya tiene un expediente: no se puede unir.');
        }

        return DB::transaction(function () use ($duplicate, $target) {
            $duplicate->messages()->update(['lead_id' => $target->id]);
            $duplicate->botNotices()->where('type', '!=', BotNoticeType::VerifyNewInquiry)->update(['lead_id' => $target->id]);

            $target->forceFill([
                'trip_data' => TripData::fillGaps(TripData::of($target), TripData::of($duplicate)),
                'ai_data' => array_merge($duplicate->ai_data ?? [], $target->ai_data ?? []),
                'ai_summary' => $target->ai_summary ?: $duplicate->ai_summary,
                'unread_count' => $target->unread_count + $duplicate->unread_count,
                'needs_human_attention' => $target->needs_human_attention || $duplicate->needs_human_attention,
                'last_inbound_at' => collect([$target->last_inbound_at, $duplicate->last_inbound_at])->filter()->max(),
                'whatsapp_last_inbound_at' => collect([$target->whatsapp_last_inbound_at, $duplicate->whatsapp_last_inbound_at])->filter()->max(),
            ])->saveQuietly();

            FollowUpRun::where('subject_type', $duplicate->getMorphClass())->where('subject_id', $duplicate->id)->delete();
            $duplicate->delete();

            return $target;
        });
    }
}
