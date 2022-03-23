<?php

namespace App\Console\Commands;

use Hardcorp\HydraClient\HydraClientFacade;
use Hardcorp\HydraClient\HydraMessageType;
use Illuminate\Console\Command;

class ConnectToHydraServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hydra:connect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connects to hydra server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        HydraClientFacade::listenForMessage(function ($payload,$type){
            var_dump($payload);
            if($type===HydraMessageType::$RECEIVED){
                //payload contains a list of ['conversation' => $conversation,
                // 'message' => $message, 'full_message' => $full_message]

                echo "New Message ...";
            }
            if($type===HydraMessageType::$SUBMITTED){
                //payload contains a list of ids of submitted messages
                //this signifies that message was successfully submitted to the gateway
                echo "Message Submit...";
            }
            if($type===HydraMessageType::$SENT){
                //payload contains a list of ids of sent messages
                echo "Message Sent...";
            }
            if($type===HydraMessageType::$FAILED){
                //payload contains a list of ids of failed messages
                echo "Message Failed...";
            }
            if($type===HydraMessageType::$SYSTEM_EVENT){
                //payload contains a list of ids of failed messages
                echo "New System Event...";
            }
        });
    }
}
