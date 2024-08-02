<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function Index(Request $request)
    {
        $post = $request->input();
        $shop = $request->input('shop');
        $host = $request->input('host');

        $shopName = $post['shop'];
        $token = User::where('name', $shopName)->first();
        $this->setMetaFiled($token);

        $apiVersion = config('services.shopify.api_version');

        $graphqlEndpoint = "https://$shopName/admin/api/$apiVersion/carrier_services.json";

        // Headers for Shopify API request
        $customHeaders = [
            'X-Shopify-Access-Token' => $token['password'],
        ];

        $this->mendatoryWebhook($shop);

        // Log::info('input logs:', ['mendatoryWebhook' => $mendatoryWebhook]);

        $data = [
            'carrier_service' => [
                'name' => 'Meetanshi Shipping Product',
                'callback_url' => env('VITE_COMMON_API_URL') . "/api/carrier/callback",
                'service_discovery' => true,
                'format' => 'json'
            ]
        ];

        // Encode the data as JSON
        $jsonData = json_encode($data);
        // Make HTTP POST request to Shopify GraphQL endpoint
        $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, $data);

        // Parse the JSON response
        $jsonResponse = $response->json();

        return view('welcome', compact('shop', 'host'));
    }

    public function common(Request $request)
    {
        $shop = $request->input('shop');
        $host = $request->input('host');
        return view('welcome', compact('shop', 'host'));
    }

    public function setMetaFiled($shop)
    {
        $url = "https://" . $shop['name'] . "/admin/api/2021-10/graphql.json";
        $query = 'mutation MetafieldDefinitionCreateMutation($input: MetafieldDefinitionInput!) {
            metafieldDefinitionCreate(definition: $input) {
                userErrors {
                    code
                    message
                    field
                    typename
                }
                typename
            }
        }';
        $variables = [
            'input' => [
                'ownerType' => 'PRODUCT',
                'namespace' => 'custom',
                'key' => 'shipping_price',
                'type' => 'number_decimal',
                'validations' => [],
                'name' => 'Shipping Price',
                'description' => '',
                'pin' => true,
                'useAsCollectionCondition' => false,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop['password'],
        ])->post($url, [
            'query' => $query,
            'variables' => $variables,
        ]);
        return $response->json();
    }

    public function mendatoryWebhook($shopDetail)
    {
        // Log::info('input logs:', ['shopDetail' => $shopDetail]);

        $token = User::where('name', $shopDetail)->first();

        $topics = [
            'customers/update',
            'customers/delete',
            'shop/update'
        ];

        $apiVersion = config('services.shopify.api_version');

        $url = "https://{$shopDetail}/admin/api/{$apiVersion}/webhooks.json";

        foreach ($topics as $topic) {
            // Create a dynamic webhook address for each topic
            $webhookAddress = "https://{$shopDetail}/{$topic}";

            // Create HTTP request for each topic
            $body = [
                'webhook' => [
                    'address' => $webhookAddress,
                    'topic' => $topic,
                    'format' => 'json'
                ]
            ];

            // Make the HTTP request (you can use Laravel's HTTP client or other libraries)
            $customHeaders = [
                'X-Shopify-Access-Token' => $token['password'], // Replace with your actual authorization token
            ];

            // Send a cURL request to the GraphQL endpoint
            $response = Http::withHeaders($customHeaders)->post($url, $body);
            $jsonResponse = $response->json();

            Log::info('input logs:', ['shopDetail' => $jsonResponse]);
        }
        return true;
    }
}
