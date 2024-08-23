<?php

namespace App\Jobs;

use App\Mail\UninstallEmail;
use App\Mail\UninstallSupportEmail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppUninstalledJob implements ShouldQueue
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
            $data = file_get_contents('php://input');

            if ($data === false) {
                Log::warning('Failed to get input data');
                return;
            }

            $data_json = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to decode JSON data:', ['data' => $data]);
                return;
            }

            $shopDomain = $data_json['domain'] ?? null;

            if (!$shopDomain) {
                Log::warning('Shop domain not found in webhook data', ['data' => $data_json]);
                return;
            }

            $user = User::where('name', $shopDomain)->first();

            if (!$user || !$user->isInstall) {
                Log::warning('User not found or already uninstalled for shop domain: ' . $shopDomain);
                return;
            }

            // Idempotent check: make sure we process this webhook only once.
            if ($user->isInstall == 0) {
                Log::info('Webhook already processed for shop domain: ' . $shopDomain);
                return;
            }

            // Begin transaction to ensure atomicity.
            DB::beginTransaction();
            try {
                // Update user status
                $user->password = "";
                $user->isInstall = 0;
                $user->save();

                // Delete products associated with the user
                Product::where('user_id', $user->id)->delete();
                Setting::where('user_id', $user->id)->delete();

                // Commit the transaction
                DB::commit();

                // Send uninstall email notifications
                Mail::to("bhushan.trivedi@meetanshi.com")->send(new UninstallEmail($data_json['shop_owner'], $shopDomain));
                // Mail::to("kaushik.panot@meetanshi.com")->send(new UninstallSupportEmail("Owner", $shopDomain));

                Log::info('User successfully uninstalled and associated data removed for shop domain: ' . $shopDomain);
                return;
            } catch (\Throwable $e) {
                // Rollback the transaction on failure
                DB::rollBack();
                Log::error('Failed to process uninstall webhook transaction:', ['error' => $e->getMessage()]);
                return;
            }
        } catch (\Throwable $e) {
            Log::error('Error processing uninstall webhook:', ['error' => $e->getMessage()]);
            return;
        }
    }
}
