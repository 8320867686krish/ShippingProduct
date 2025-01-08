<?php

namespace App\Jobs;

use App\Models\Charge;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppSubscriptionsUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $charge = request()->input()['app_subscription'];

            $shopDomain = request()->header()['x-shopify-shop-domain'][0];

            $shop = User::where('name', $shopDomain)->first();

            $chargeId = explode('/', $charge['admin_graphql_api_id']);

            Log::info("Call AppSubscriptionsJob66666", ['chargeId' => $chargeId]);

            $apiVersion = config('services.shopify.api_version');

            $url = "https://{$shop['name']}/admin/api/{$apiVersion}/recurring_application_charges/{$chargeId[4]}.json";

            $customHeaders = [
                'X-Shopify-Access-Token' => $shop['password'], // Replace with your actual authorization token
            ];

            // Send a cURL request to the GraphQL endpoint
            $response = Http::withHeaders($customHeaders)->get($url);
            $jsonResponsedata = $response->json();
            $jsonResponse = $jsonResponsedata['recurring_application_charge'];

            Charge::updateOrCreate(['user_id'=>$shop['id']], [
                'charge_id' => $jsonResponse['id'],
                'test'=>$jsonResponse['test'],
                'status'=>$jsonResponse['status'],
                'name'=>$jsonResponse['name'],
                'type' => '',
                'price' => $jsonResponse['price'],
                'interval' => 'EVERY_30_DAYS',
                'trial_days' => $jsonResponse['trial_days'],
                'billing_on' => $jsonResponse['billing_on'],
                'activated_on' => $jsonResponse['activated_on'],
                'trial_ends_on' => $jsonResponse['trial_ends_on'],
                'user_id' => $shop['id']
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            Log::info("Error AppSubscriptionsUpdateJob", ['Throwable' => $th->getMessage()]);
        }

        Log::info("Call AppSubscriptionsJob66666", ['request' => request()->input()]);
    }
}
