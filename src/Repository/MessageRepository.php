<?php


namespace Hardcorp\HydraClient\Repository;


use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

interface MessageRepository
{
function getMessages(): Collection;
function getEvents(string $table): Collection;
function deleteMessages(Collection $ids);
function deleteEvents(string $table,Collection $ids);
function deleteMessageStatuses(array $ids, string $type);
function saveMessage(array $message);
function getConversationsQuery():Builder;
function updateConversation(array $conversation);
function updateConversationStatus(array $ids,string $status);
}