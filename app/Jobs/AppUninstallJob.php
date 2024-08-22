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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppUninstallJob implements ShouldQueue
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
        try {
            $data = file_get_contents('php://input');

            if ($data === false) {
                Log::warning('Failed to get input data');
                return;
            }

            $data_json = json_decode($data, true);

            if ($data_json === null) {
                Log::warning('Failed to decode JSON data:', ['data' => $data]);
                return;
            }

            $shopDomain = $data_json['domain'];

            // Check if this webhook has already been processed
            if ($this->hasAlreadyProcessed($shopDomain)) {
                Log::info('Webhook already processed for shop domain:', ['shopDomain' => $shopDomain]);
                return;
            }

            $user = User::where('name', $shopDomain)->first();

            if ($user) {
                $user->password = "";
                $user->save();
                Product::where('user_id', $user->id)->delete();
            } else {
                Log::warning('User not found for shop domain: ' . $shopDomain);
                return;
            }

            $this->markAsProcessed($shopDomain);

            Mail::to("bhushan.trivedi@meetanshi.com")->send(new UninstallEmail($data_json['shop_owner'], $shopDomain));
            // Uncomment to send the second email
            Mail::to("kaushik.panot@meetanshi.com")->send(new UninstallSupportEmail("Owner", $shopDomain));

            Log::info('User password successfully reset and products deleted!');
        } catch (\Throwable $e) {
            Log::error('Error processing webhook:', ['error' => $e->getMessage()]);
        }
    }

    private function deleteMetafieldDefinition($user, $metafieldId, $deleteAllAssociatedMetafields = false)
    {
        $url = "https://" . $user->name . "/admin/api/2024-01/graphql.json";
        $query = <<<GQL
            mutation MetafieldDefinitionDeleteMutation(\$id: ID!, \$deleteAllAssociatedMetafields: Boolean) {
                metafieldDefinitionDelete(
                    id: \$id
                    deleteAllAssociatedMetafields: \$deleteAllAssociatedMetafields
                ) {
                    deletedDefinitionId
                    userErrors {
                        field
                        message
                        code
                    }
                }
            }
            GQL;

        $variables = [
            'id' => "gid://shopify/MetafieldDefinition/{$metafieldId}",
            'deleteAllAssociatedMetafields' => $deleteAllAssociatedMetafields,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $user->password,
        ])->post($url, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']['metafieldDefinitionDelete']['deletedDefinitionId'])) {
                Log::info('Metafield definition successfully deleted:', [
                    'shop' => $user->name,
                    'deletedDefinitionId' => $data['data']['metafieldDefinitionDelete']['deletedDefinitionId'],
                ]);
                return true;
            } elseif (!empty($data['data']['metafieldDefinitionDelete']['userErrors'])) {
                Log::error('Failed to delete Metafield definition due to user errors:', [
                    'shop' => $user->name,
                    'errors' => $data['data']['metafieldDefinitionDelete']['userErrors'],
                ]);
            } else {
                Log::warning('Metafield definition deletion request did not return a deletedDefinitionId.', [
                    'shop' => $user->name,
                    'response' => $data,
                ]);
            }
        } else {
            $responseBody = $response->json();
            if (isset($responseBody['errors'])) {
                Log::error('API Error:', [
                    'shop' => $user->name,
                    'error' => $responseBody['errors'],
                ]);
            } else {
                Log::error('GraphQL request failed:', [
                    'shop' => $user->name,
                    'response' => $responseBody,
                ]);
            }
        }

        return false;
    }

    private function hasAlreadyProcessed($shopDomain)
    {
        // Example logic: Check if this domain is in cache or a database
        return cache()->has("processed_uninstall_{$shopDomain}");
    }

    private function markAsProcessed($shopDomain)
    {
        // Example logic: Mark this domain as processed in cache or a database
        cache()->put("processed_uninstall_{$shopDomain}", true, now()->addMinutes(10));
    }
}
