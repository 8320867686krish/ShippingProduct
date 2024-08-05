<?php

namespace App\Http\Controllers;

use App\Mail\InstallMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $this->getStoreOwnerEmail($shop);

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

        Log::info('input logs:', ['shopurl' => $shopDetail]);

        $url = "https://{$token['name']}/admin/api/{$apiVersion}/webhooks.json";
        $envUrl = env('VITE_COMMON_API_URL');

        foreach ($topics as $topic) {
            // Create a dynamic webhook address for each topic
            $webhookAddress = "https://{$envUrl}/{$topic}";

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

    protected function getStoreOwnerEmail($shop)
    {
        $user = User::where('name', $shop)->pluck('password')->first();
        $apiVersion = config('services.shopify.api_version');
        $shop_url = "https://{$shop}/admin/api/{$apiVersion}/shop.json";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $shop_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Shopify-Access-Token:" . $user
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            $data = json_decode($response, true);
            // Log::info('input logs:', ['shopDetail' => $data]);
            if (@$data['shop']) {
                // $storeOwnerEmail = "kaushik.panot@meetanshi.com";
                $storeOwnerEmail = $data['shop']['email'];
                $store_name = $data['shop']['name'];
                // User::where('name', $shop)->update(['store_owner_email' => $storeOwnerEmail, 'store_name' => $store_name]);
                $details = [
                    'title' => 'Thank You for Installing AI Content Generator for Shopify - Meetanshi',
                    'name' => $store_name
                ];

                $name = $data['shop']['shop_owner'];
                $shopDomain = $data['shop']['domain'];

                Mail::to($storeOwnerEmail)->send(new InstallMail($name, $shopDomain));

                return true;
            }
        }
    }
}
