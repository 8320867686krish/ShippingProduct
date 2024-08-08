<?php

namespace App\Jobs;

use App\Models\Charge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class App_subscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("App_subscriptionsJob Call");

        $data_json = $this->data;
        $data = file_get_contents('php://input');
        $data_json = json_decode($data, true);
        Log::info(print_r($data_json));

        if (!empty($data_json['app_subscription'])) {
            Log::info("cccccc");
            $data = $data_json['app_subscription'];
            $status = $data['status'];
            Log::info($data_json['app_subscription']);


            if ($status !== 'ACTIVE') {

                $charge_id = str_replace('gid://shopify/AppSubscription/', '', $data['admin_graphql_api_id']);

                Charge::where('charge_id', $charge_id)->update(['status' => $status]);
            }
        }
    }
}
