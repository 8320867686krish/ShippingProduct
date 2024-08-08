<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Kyon147\LaravelShopify\Facades\ShopifyApp;
use Kyon147\LaravelShopify\Models\Shop;

class RecurringChargeController extends Controller
{
    public function createRecurringCharge(Request $request)
    {
        $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Token not provided.'
            ], 400);
        }

        $token = User::where('name', $shop)->first();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $query = '
            mutation appSubscriptionCreate(
                $name: String!,
                $returnUrl: URL!,
                $trialDays: Int,
                $test: Boolean,
                $lineItems: [AppSubscriptionLineItemInput!]!
            ) {
                appSubscriptionCreate(
                    name: $name,
                    returnUrl: $returnUrl,
                    trialDays: $trialDays,
                    test: $test,
                    lineItems: $lineItems
                ) {
                    appSubscription {
                        id
                        name
                        status
                        createdAt
                        trialDays
                        currentPeriodEnd
                    }
                    confirmationUrl
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'name' => 'Test Subscription', // More descriptive name
            'returnUrl' => url('recurring/confirm'), // Ensure this URL is correct and accessible
            'trialDays' => 7,
            'test' => true,
            'lineItems' => [
                [
                    'plan' => [
                        'appRecurringPricingDetails' => [
                            'price' => [
                                'amount' => 20.00,
                                'currencyCode' => 'USD',
                            ],
                            'interval' => 'EVERY_30_DAYS', // Ensure this value matches the schema
                        ],
                    ],
                ],
            ],
        ];

        $requestBody = [
            'query' => $query,
            'variables' => $variables, // Include the variables here
        ];

        $apiVersion = config('services.shopify.api_version');

        $graphqlEndpoint = "https://{$shop}/admin/api/{$apiVersion}/graphql.json";

        $customHeaders = [
            'X-Shopify-Access-Token' => $token['password'], // Replace with your actual authorization token
        ];

        // Send a cURL request to the GraphQL endpoint
        $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, $requestBody);
        $jsonResponse = $response->json();

        $charge_id = str_replace('gid://shopify/AppSubscription/', '', $jsonResponse['data']['appSubscriptionCreate']['appSubscription']['id']);

        // DB::table('charges')->insert([
        //     'charge_id' => $charge_id,
        //     'test' => true,
        //     'price' => $plans->price,
        //     'type' => $plans->type,
        //     'user_id' => $token['id'],
        //     'interval' => $plans->interval,
        //     'plan_id' => $plans->id,
        // ]);

        Charge::updateOrCreate(
            [
                'user_id' => $token['id'],
                'plan_id' => 1,
                'test' => true,
            ],
            [
                'charge_id' => $charge_id,
                'test' => true,
                'price' => 20,
                'type' => 'Fst',
                'user_id' => $token['id'],
                'interval' => 'EVERY_30_DAYS',
                'plan_id' => 1,
            ]
        );

        $token = User::where('id', $token['id'])->first();

        return response()->json(['data' => $jsonResponse['data']['appSubscriptionCreate']['confirmationUrl']]);

    }

    public function confirmRecurringCharge(Request $request)
    {
        $chargeId = $request->query('charge_id');

        $userId = Charge::where('charge_id', $chargeId)->pluck('user_id')->first();

        $shop = User::where('id', $userId)->first();

        $apiVersion = config('services.shopify.api_version');

        $url = "https://{$shop['name']}/admin/api/{$apiVersion}/recurring_application_charges/{$chargeId}.json";
        $customHeaders = [
            'X-Shopify-Access-Token' => $shop['password'], // Replace with your actual authorization token
        ];

        // Send a cURL request to the GraphQL endpoint
        $response = Http::withHeaders($customHeaders)->get($url);
        $jsonResponsedata = $response->json();
        $jsonResponse = $jsonResponsedata['recurring_application_charge'];

        Charge::where('charge_id', $chargeId)->update([
            'status' => $jsonResponse['status'],
            'name' => $jsonResponse['name'],
            'trial_days' => $jsonResponse['trial_days'],
            'trial_ends_on' => $jsonResponse['trial_ends_on'],
            'activated_on' => $jsonResponse['activated_on'],
            'billing_on' => $jsonResponse['activated_on'],
        ]);

        // $recurringCharge = $shop->api()->rest('GET', "/admin/recurring_application_charges/{$chargeId}.json");

        // if ($recurringCharge['body']['recurring_application_charge']['status'] == 'accepted') {
            // Activate the charge
            // $shop->api()->rest('POST', "/admin/recurring_application_charges/{$chargeId}/activate.json");

            $redirect_url = "https://{$shop['name']}/admin/apps/kaushik-test";

            return redirect()->route('home')->with('success', 'Recurring charge accepted.');
        // }

        // return redirect()->route('home')->with('error', 'Recurring charge was not accepted.');
    }
}
