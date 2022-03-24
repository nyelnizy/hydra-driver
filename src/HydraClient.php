<?php


namespace Hardcorp\HydraClient;


use Hardcorp\HydraClient\Repository\MessageRepository;
use Hardcorp\HydraClient\Repository\HydraMessageRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HydraClient
{
    private $base64Files = [];
    /**
     * @var HydraMessageRepository
     */
    private $hydraMessageRepo;

    public function __construct(MessageRepository $repository)
    {
        $this->hydraMessageRepo = $repository;
    }

    /**
     * @param callable $callback
     */
    public function listenForMessage(callable $callback)
    {
        $url = config("hydra-client.hydra_server");
        \Ratchet\Client\connect("$url/incoming_messages")->then(function ($conn) use ($callback) {
            //whenever webapp comes online try consuming pending messages
            echo "Connected to Server...\n";
            $this->consumeIncomingMessage($callback);
            $conn->on('message', function ($msg) use ($conn, $callback) {
                $data = json_decode($msg);
                $status = $data->status;
                if ($status === "submitted") {
                    $callback($data->payload, HydraMessageType::$SUBMITTED);
                    $this->hydraMessageRepo->deleteMessageStatuses($data->payload, 'submitted');
                }
                if ($status === 'sent') {
                    $this->hydraMessageRepo->updateConversationStatus($data->payload, 'sent');
                    $callback($data->payload, HydraMessageType::$SENT);
                    $this->hydraMessageRepo->deleteMessageStatuses($data->payload, 'sent');
                }
                if ($status === 'received') {
                    $this->consumeIncomingMessage($callback);
                }
                if ($status === "failed") {
                    $this->hydraMessageRepo->updateConversationStatus($data->payload, 'failed');
                    $callback($data->payload, HydraMessageType::$FAILED);
                    $this->hydraMessageRepo->deleteMessageStatuses($data->payload, 'failed');
                }
                if ($status === "system_events_received") {
                    $this->consumeSystemEvents($callback);
                }
            });
            $conn->on('close', function ($code = null, $reason = null) use ($callback) {
                echo "Could not connect: closed\n";
                $this->listenForMessage($callback);
            });
        }, function ($e) use ($callback) {
            echo "Could not connect: {$e->getMessage()}\n";
            $this->listenForMessage($callback);
        });
    }

    /**
     * @param int $id_mr_friday
     * @param int $thread_id
     * @param string $device_id
     * @param string $sim_serial
     * @param string $participant_address
     * @param string $message
     * @param int $subscription_id
     */
    public function sendMessage(int $id_mr_friday,
                                int $thread_id,
                                string $device_id,
                                string $sim_serial,
                                string $participant_address,
                                string $message,
                                int $subscription_id = null)
    {

        $manage_chats = config("hydra-client.manage_chats");
        if($manage_chats){
            $this->hydraMessageRepo->saveMessage([
                'thread_id' => $thread_id,
                'id_mr_friday' => $id_mr_friday,
                'device_id' => $device_id,
                'sim_serial' => $sim_serial,
                'subscription_id' => $subscription_id ?? -1,
                'participant_address' => $participant_address,
                'message' => $message,
                'attachments' => json_encode($this->base64Files),
                'created_at' => now()
            ]);
        }
        $conversation = [
            'thread_id' => $thread_id,
            'sim_serial' => $sim_serial,
            'last_message' => $message,
            'has_attachment' => count($this->base64Files) > 0,
            'can_reply' => true,
            'last_message_status' => 'submitted',
            'last_message_id_mf' => $id_mr_friday,
            'date' => now(),
        ];
        $this->hydraMessageRepo->updateConversation($conversation);
        $url = config("hydra-client.hydra_server");

        \Ratchet\Client\connect("$url/signal")->then(function ($conn) use ($sim_serial) {
            $conn->send(json_encode(['channel' => 'outgoing_messages_' . $sim_serial]));
            $conn->close();
        }, function ($e) {
            throw new \Exception($e);
        });
    }

    /**
     * @param array $attachments
     * @return array
     */
    public function addAttachments(array $attachments): array
    {
        $filePaths = [];
        foreach ($attachments as $attachment) {
            $res = $this->uploadOutgoingMessageAttachment($attachment);
            $filePaths[] = $res['path'];
            $this->base64Files[] = ['file' => $res['b64'], 'extension' => $res['extension'], 'mime' => $res['mime']];
        }
        return $filePaths;
    }

    /**
     * @param array $filter
     * @param null $page_size
     * @return mixed
     */
    public function getConversations($filter = [], $page_size = null)
    {
        $query = $this->hydraMessageRepo->getConversationsQuery();
        foreach ($filter as $column => $value) {
            $query->where($column, $value);
        }
        $query->orderBy('date', 'desc');
        if (!is_null($page_size)) {
            return $query->paginate($page_size);
        }
        return $query->get();
    }

    /**
     * @param callback $callback
     * @throws \Exception
     */
    private function consumeIncomingMessage(callable $callback)
    {
        echo "\nConsuming Pending Messages...\n";
        try {
            $messages = $this->hydraMessageRepo->getMessages();
            if ($messages->count() < 1) {
                return;
            }
            $sms_chats = [];
            foreach ($messages as $message) {
                $attachments = json_decode($message->attachments);
                $has_attachment = $attachments !== "null" && !is_null($attachments) && count($attachments) > 0;
                $conversation = [
                    'thread_id' => $message->thread_id,
                    'subscription_id' => $message->subscription_id,
                    'device_id' => $message->device_id,
                    'sim_serial' => $message->sim_serial,
                    'participant_address' => $message->participant_address,
                    'participant_name' => $message->participant_name,
                    'participant_initials' => $message->participant_initials,
                    'avatar_bg' => $message->avatar_bg,
                    'last_message' => $message->message,
                    'last_message_status' => 'received',
                    'last_message_id_mf' => null,
                    'has_attachment' => $has_attachment,
                    'can_reply' => $message->can_reply,
                    'date' => $message->read_at,
                ];
                $mess = [
                    'thread_id' => $message->thread_id,
                    'device_id' => $message->device_id,
                    'sim_serial' => $message->sim_serial,
                    'message' => $message->message,
                    'date' => $message->read_at,
                    'received_at' => $message->received_at,
                ];
                $mess['attachments'] = [];
                if ($has_attachment) {
                    $mess['attachments'] = $this->uploadIncomingMessageAttachments($attachments);
                }
                $manage = config("hydra-client.manage_chats");
                    if($manage){
                        $this->hydraMessageRepo->updateConversation($conversation);
                    }
                $full_message = $conversation;
                $full_message['received_at'] = $mess['received_at'];
                $sms_chats[] = ['conversation' => $conversation, 'message' => $mess, 'full_message' => $full_message];
            }
            $callback($sms_chats, HydraMessageType::$RECEIVED);
            $ids = $messages->pluck('id');
            $this->hydraMessageRepo->deleteMessages($ids);
        } catch (\Exception $e) {
            var_dump($e->getMessage()."\n");
            throw new \Exception($e);
        }
    }

    /**
     * @param array $attachments
     * @return array
     */
    private function uploadIncomingMessageAttachments(array $attachments): array
    {
        $paths = [];
        foreach ($attachments as $attachment) {
            $ext = $attachment->extension;
            $name = Str::random(20) . "_" . now()->milliseconds . "." . $ext;
            $contents = base64_decode($attachment->file);
            $folder = config("hydra-client.attachments_folder");
            $path = "$folder/$name";
            Storage::put($path, $contents);
            $paths[] = $path;
        }
        return $paths;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    private function uploadOutgoingMessageAttachment(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        $mime = $file->getMimeType();
        $folder = config("hydra-client.attachments_folder");
        $path = $file->store($folder);
        $b64 = base64_encode($file->getContent());
        return ["path" => $path, "extension" => $extension, "b64" => $b64, 'mime' => $mime];
    }

    private function consumeSystemEvents(callable $callback)
    {
        echo "\nConsuming Pending Events...\n";
        $events = $this->hydraMessageRepo->getSystemEvents();
        $events_to_deliver = [];
        foreach ($events as $event) {
            $event = (array)$event;
            $ev['type'] = $event['type'];
            $ev['sim_serial'] = $event['sim_serial'];
            $ev['device_id'] = $event['device_id'];
            $ev['date'] = $event['date'];
            $events_to_deliver[] = $ev;
        }
        $callback($events_to_deliver, HydraMessageType::$SYSTEM_EVENT);
        $this->hydraMessageRepo->deleteSystemEvents($events->pluck('id'));
    }
}