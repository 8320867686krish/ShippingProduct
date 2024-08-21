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

            // Check if the received data is valid JSON
            if ($data === false) {
                Log::warning('Failed to get input data');
                return;
            }

            $data_json = json_decode($data, true);

            // Check if JSON decoding is successful
            if ($data_json === null) {
                Log::warning('Failed to decode JSON data:', ['data' => $data]);
                return;
            }

            Log::info('Decoded Webhook Data:', ['data' => $data_json]);

            $to = "bhushan.trivedi@meetanshi.com";
            // $to = $data_json['email'];
            $name = $data_json['shop_owner'];
            $shopDomain = $data_json['domain'];

            Log::info('Webhook Information:', [
                'to' => $to,
                'name' => $name,
                'shopDomain' => $shopDomain,
            ]);

            $user = User::where('name', $shopDomain)->first();

            if ($user) {
                $user->password = "";
                $user->save();
                Product::where('user_id', $user->id)->delete();
            } else {
                Log::warning('User not found for shop domain: ' . $shopDomain);
            }

            Mail::to($to)->send(new UninstallEmail($name, $shopDomain));

            Mail::to("kaushik.panot@meetanshi.com")->send(new UninstallSupportEmail("Owner", $shopDomain));

            Log::info('User password successfully!');
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
}
