<?php

namespace App\Http\Controllers;

use App\Mail\InstallMail;
use App\Mail\InstallSupportMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

class HomeController extends Controller
{
    public function Index(Request $request)
    {
        $post = $request->input();
        $shop = $request->input('shop');
        $host = $request->input('host');

        $apiVersion = config('services.shopify.api_version');

        $shopName = $post['shop'];
        $token = User::where('name', $shop)->first();
        $shop_exist =  $token;

        $graphqlEndpoint = "https://$shop/admin/api/$apiVersion/graphql.json";

        // Headers for Shopify API request
        $customHeaders = [
            'X-Shopify-Access-Token' => $token['password'],
        ];

        // GraphQL query for creating a carrier service
        $query = <<<'GRAPHQL'
            mutation carrierServiceCreate($input: DeliveryCarrierServiceCreateInput!) {
                carrierServiceCreate(input: $input) {
                    carrierService {
                        id
                        name
                        callbackUrl
                        supportsServiceDiscovery
                        active
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

        // Variables for the mutation
        $variables = [
            'input' => [
                'name' => 'Meetanshi Shipping Product',
                'callbackUrl' => env('VITE_COMMON_API_URL') . "/api/carrier/callback",
                'supportsServiceDiscovery' => true,
                'active' => true,
            ],
        ];

        // Prepare the data for the GraphQL request
        $data = [
            'query' => $query,
            'variables' => $variables,
        ];

        // Make the HTTP POST request to Shopify GraphQL endpoint
        $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, $data);

        // Parse the JSON response
        $jsonResponse = $response->json();

        Log::error("GraphQL User Error: ", ["jsonResponse"=>$jsonResponse]);

        // Check for errors in the response
        if (isset($jsonResponse['data']['carrierServiceCreate']['userErrors']) && !empty($jsonResponse['data']['carrierServiceCreate']['userErrors'])) {
            $errors = $jsonResponse['data']['carrierServiceCreate']['userErrors'];
            foreach ($errors as $error) {
                Log::error("GraphQL User Error: " . $error['message'], ['field' => $error['field']]);
            }
        }

        // Check for carrier service in the response
        if (isset($jsonResponse['data']['carrierServiceCreate']['carrierService'])) {
            $carrierService = $jsonResponse['data']['carrierServiceCreate']['carrierService'];
            Log::info('Carrier Service Created Successfully', $carrierService);
        }

        if ($token['isInstall'] == 0) {
            $token->isInstall = 1;
            $token->save();
            // $this->mendatoryWebhook($shop);
            $this->setMetaFiled($token);
            $this->getStoreOwnerEmail($shop);
        }

        return view('welcome', compact('shop', 'host', 'shop_exist'));
    }

    public function common(Request $request)
    {
        $shop = $request->input('shop');
        $shop_exist = User::where('name', $shop)->first();
        $host = $request->input('host');
        return view('welcome', compact('shop', 'host', 'shop_exist'));
    }

    private function setMetaFiled($shop)
    {
        $url = "https://" . $shop['name'] . "/admin/api/2024-01/graphql.json";
        $query = 'mutation MetafieldDefinitionCreateMutation($input: MetafieldDefinitionInput!) {
                    metafieldDefinitionCreate(definition: $input) {
                        createdDefinition {
                            id
                            name
                            namespace
                            key
                        }
                        userErrors {
                            code
                            message
                            field
                        }
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

        if ($response->successful()) {
            $data = $response->json();
            // Log::error('GraphQL request failed:', ['response' => $data]);
            if (isset($data['data']['metafieldDefinitionCreate']['createdDefinition']['id'])) {
                $metafieldId = $data['data']['metafieldDefinitionCreate']['createdDefinition']['id'];
                $explod = explode('/', $metafieldId);
                Log::info('Metafield Definition created successfully:', ['id' => $explod]);
                User::where('id', $shop['id'])->update(['metafield_id' => $explod[4]]);
                return $metafieldId;
            }
            return null;
        } else {
            Log::error('GraphQL request failed:', ['response' => $response->json()]);
            return null;
        }
    }

    private function getStoreOwnerEmail($shop)
    {
        $user = User::where('name', $shop)->pluck('password')->first();
        $apiVersion = config('services.shopify.api_version');
        $graphqlEndpoint = "https://$shop/admin/api/{$apiVersion}/graphql.json";

        // Headers for Shopify API request
        $customHeaders = [
            'X-Shopify-Access-Token' => $user,
        ];

        $query = <<<GRAPHQL
                {
                    shop {
                        name
                        email
                    }
                }
                GRAPHQL;

        // Make HTTP POST request to Shopify GraphQL endpoint
        $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, [
            'query' => $query,
        ]);

        $data = $response->json();
        if (@$data['data']['shop']) {
            $storeOwnerEmail = $jsonResponse['data']['shop']['email'] ?? "sanjay@meetanshi.com";

            $store_name = $data['data']['shop']['name'];
            $shopDomain = $data['data']['shop']['name'];

            try {
                // Send mail to the store owner
                Mail::to($storeOwnerEmail)->send(new InstallMail($store_name, $shopDomain));
                
                // Send mail to the support team
                Mail::to("sanjay@meetanshi.com")->send(new InstallSupportMail("Owner", $shopDomain));
            } catch (TransportException $e) {
                Log::error("Mail sending failed for {$storeOwnerEmail}: " . $e->getMessage());
            }          

            return true;
        } else {
            return false;
        }
    }
}
