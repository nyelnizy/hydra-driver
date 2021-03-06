<?php


namespace Hardcorp\HydraClient\Repository;

use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class HydraMessageRepository implements MessageRepository
{
    function getMessages(): Collection
    {
        return DB::connection('hydra')->table('incoming_messages')->get();
    }

    function deleteMessages(Collection $ids)
    {
        DB::connection('hydra')
            ->table('incoming_messages')
            ->whereIn('id', $ids)
            ->delete();
    }

    function deleteMessageStatuses(array $ids, string $type)
    {
        DB::connection('hydra')
            ->table('message_statues')
            ->where('type', $type)
            ->whereIn('message_id', $ids)
            ->delete();
    }

    function saveMessage(array $message)
    {
        DB::connection('hydra')
            ->table('outgoing_messages')
            ->insert($message);
    }

    function getConversationsQuery(): Builder
    {
        return DB::table('hydra_sms_conversations')
            ->select(['thread_id',
                'subscription_id',
                'device_id',
                'sim_serial',
                'participant_address',
                'participant_name',
                'participant_initials',
                'avatar_bg',
                'last_message',
                'last_message_status',
                'last_message_id_mf',
                'has_attachment',
                'can_reply',
                'date']);
    }

    function updateConversation(array $conversation)
    {
        $check = ['thread_id' => $conversation['thread_id'], 'sim_serial' => $conversation['sim_serial']];
        DB::table('hydra_sms_conversations')->upsert($check, $conversation);
    }

    function updateConversationStatus(array $ids, string $status)
    {
        DB::table('hydra_sms_conversations')
            ->whereIn('last_message_id', $ids)
            ->update(['last_message_status' => $status]);
    }

    function getEvents(string $table): Collection
    {
        return DB::connection('hydra')
            ->table($table)
            ->get();
    }

    function deleteEvents(string $table,Collection $ids)
    {
        DB::connection('hydra')
            ->table($table)
            ->whereIn('id', $ids)
            ->delete();
    }
}