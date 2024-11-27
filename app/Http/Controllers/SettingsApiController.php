<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsApiController extends Controller
{
    public function demo()
    {
        $user = User::where('name', 'jaypal-demo.myshopify.com')->first();
        $url = "https://" . $user['name'] . "/admin/api/2024-01/graphql.json";
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
            'id' => "gid://shopify/MetafieldDefinition/{$user['metafield_id']}",
            'deleteAllAssociatedMetafields' => false,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $user['password'],
        ])->post($url, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']['metafieldDefinitionDelete']['deletedDefinitionId'])) {
                Log::info('Metafield definition successfully deleted:', [
                    'shop' => $user['name'],
                    'deletedDefinitionId' => $data['data']['metafieldDefinitionDelete']['deletedDefinitionId'],
                ]);
                return $data['data']['metafieldDefinitionDelete']['deletedDefinitionId'];
            } elseif (!empty($data['data']['metafieldDefinitionDelete']['userErrors'])) {
                Log::error('Failed to delete Metafield definition due to user errors:', [
                    'shop' => $user['name'],
                    'errors' => $data['data']['metafieldDefinitionDelete']['userErrors'],
                ]);
            } else {
                Log::warning('Metafield definition deletion request did not return a deletedDefinitionId.', [
                    'shop' => $user['name'],
                    'response' => $data,
                ]);
            }
        } else {
            $responseBody = $response->json();
            if (isset($responseBody['errors'])) {
                Log::error('API Error:', [
                    'shop' => $user['name'],
                    'error' => $responseBody['errors'],
                ]);
            } else {
                Log::error('GraphQL request failed:', [
                    'shop' => $user['name'],
                    'response' => $responseBody,
                ]);
            }
        }

        return null;
    }

    public function index(Request $request)
    {
        try {
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            // Fetch the token for the shop
            $token = User::where('name', $shop)->pluck('id')->first();

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $setting = Setting::with('productdata')->where('user_id', $token)->first();

            return response()->json([
                'status' => true,
                'message' => 'Setting list retrieved successfully.',
                'setting' => $setting,
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Database error when retrieving setting list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Database error occurred.'], 500);
        } catch (\Exception $ex) {
            Log::error('Unexpected error when retrieving setting list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

    private function setMetafield(array $metafields, $password, $shop)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $password,
            'Content-Type' => 'application/json',
        ])->post("https://{$shop}/admin/api/2024-01/graphql.json", [
            'query' => 'mutation MetafieldsSet($metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: $metafields) {
                    metafields {
                        id
                        namespace
                        key
                        value
                    }
                    userErrors {
                        field
                        message
                        elementIndex
                    }
                    __typename
                }
            }',
            'variables' => [
                'metafields' => $metafields
            ]
        ]);

        Log::info("updadeted metafield", ['response' => $response->json()]);
        return $response->json();
    }

    public function store(Request $request)
    {
        try {
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            // Fetch the token for the shop
            $token = User::where('name', $shop)->first();

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }
            $plans = Charge::where('user_id',$token['id'])->pluck('status')->first();

            if($plans != 'active'){
              return response()->json(['status' => false,'isExpired'=>false, 'message' => 'Your Plan hass been expired']);
            }
            $validator = Validator::make($request->all(), [
                'enabled' => 'required|boolean',
                'title' => 'required|string|max:254',
                'shipping_rate' => 'required|numeric|in:1,2',
                'shipping_rate_calculation' => 'required|integer|in:1,2,3',
                'method_name' => 'nullable|string|max:254',
                'product_shipping_cost' => 'required|boolean',
                'rate_per_item' => 'nullable|numeric|min:0',
                'handling_fee' => 'nullable|numeric|min:0',
                'applicable_countries' => 'required|boolean',
                'countries' => 'required_if:applicable_countries,1',
                'method_if_not_applicable' => 'nullable|boolean',
                'min_order_amount' => 'nullable|numeric|min:0',
                'max_order_amount' => 'nullable|numeric|min:0',
                'productdata.*.checked' => 'boolean',
                // 'productdata.*.price' => 'required_if:productdata.*.checked,true',
                'productdata.*.product_id' => 'required_if:productdata.*.checked,true',
                'productdata.*.title' => 'required_if:productdata.*.checked,true',
                'productdata.*.value' => [
                    'required_if:productdata.*.checked,true',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1]; // Extract the index from the attribute name

                        // Check if checked is true
                        if ($request->input("productdata.$index.checked") == true) {
                            if (!is_numeric($value)) {
                                $fail("The $attribute must be a numeric value.");
                            } elseif ($value < 0) {
                                $fail("The $attribute must be at least 0.");
                            }
                        }
                    },
                ],
            ], [
                'enabled.required' => 'The enabled field is required.',
                'enabled.boolean' => 'The enabled field must be true or false.',
                'title.required' => 'The title field is required.',
                'title.string' => 'The title must be a string.',
                'title.max' => 'The title may not be greater than 254 characters.',
                'shipping_rate.required' => 'The shipping rate is required.',
                'shipping_rate.numeric' => 'The shipping rate must be a number.',
                'shipping_rate.in' => 'The shipping rate must be 1 or 2.',
                'shipping_rate_calculation.required' => 'The shipping rate calculation is required.',
                'shipping_rate_calculation.integer' => 'The shipping rate calculation must be an integer.',
                'shipping_rate_calculation.in' => 'The shipping rate calculation must be 1, 2, or 3.',
                'method_name.required' => 'The method name is required.',
                'method_name.string' => 'The method name must be a string.',
                'method_name.max' => 'The method name may not be greater than 254 characters.',
                'product_shipping_cost.required' => 'The product shipping cost field is required.',
                'product_shipping_cost.boolean' => 'The product shipping cost field must be true or false.',
                'rate_per_item.numeric' => 'The rate per item must be a number.',
                'handling_fee.numeric' => 'The handling fee must be a number.',
                'rate_per_item.min' => 'The rate per item must be at least 0.',
                'handling_fee.min' => 'The handling fee must be at least 0.',
                'applicable_countries.required' => 'The applicable countries field is required.',
                'countries.required_if' => 'The countries field is required when applicable countries is set to Specific Countries.',
                'method_if_not_applicable.boolean' => 'The method if not applicable field must be true or false.',
                'min_order_amount.required' => 'The minimum order amount is required.',
                'min_order_amount.numeric' => 'The minimum order amount must be a number.',
                'max_order_amount.required' => 'The maximum order amount is required.',
                'max_order_amount.numeric' => 'The maximum order amount must be a number.',
                'min_order_amount.min' => 'The minimum order amount must be at least 0.',
                'max_order_amount.min' => 'The maximum order amount must be at least 0.',
                'productdata.array' => 'The product data must be an array.',
                'productdata.*.checked.boolean' => 'The checked value must be true or false.',
                // 'productdata.*.price.required_if' => 'The price is required when the product is checked.',
                'productdata.*.product_id.required_if' => 'The product ID is required when the product is checked.',
                'productdata.*.title.required_if' => 'The title is required when the product is checked.',
                'productdata.*.value.required_if' => 'The value is required when the product is checked.',
                'productdata.*.value.min' => 'The product value must be at least 0.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $post = $request->input();
            $post['user_id'] = $token['id'];

            // Use updateOrCreate method
            $setting = Setting::updateOrCreate(['user_id' => $token['id']], $post);

            if (null !== $request->input('productdata')) {
                $metafields = [];

                foreach ($request->input('productdata') as $product) {
                    if (isset($product)) {
                        $productValue = $product['checked'] == 1 ? "{$product['value']}" : "0.00";
                        if ($product['checked']) {
                            $productData = [
                                "user_id" => $token['id'],
                                "setting_id" => $setting->id,
                                "product_id" => $product['product_id'],
                                "title" => $product['title'],
                                "value" => $product['value'],
                                "checked" => $product['checked']
                            ];
                            Product::updateOrCreate(['product_id' => $product['product_id'], 'setting_id' => $setting->id], $productData);
                        } else {
                            Product::where([
                                'product_id' => $product['product_id'],
                                'setting_id' => $setting->id,
                            ])->delete();
                        }

                        Log::info('input logs:', [$product['product_id'] => $productValue]);

                        $metafields[] = [
                            'namespace' => "custom",
                            'key' => "shipping_price",
                            'type' => "number_decimal",
                            'value' => $productValue,
                            'ownerId' => "gid://shopify/Product/{$product['product_id']}"
                        ];
                    }
                }

                $this->setMetafield($metafields, $token['password'], $shop);
            }

            if ($setting->wasRecentlyCreated) {
                $message = 'Setting added successfully.';
            } else {
                $message = 'Setting updated successfully.';
            }

            return response()->json(['status' => true, 'message' => $message, 'setting' => $setting]);
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Database error when retrieving setting add', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Database error occurred.'], 500);
        } catch (\Exception $ex) {
            Log::error('Unexpected error when retrieving setting add', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

    public function settingBasedToken(Request $request)
    {
        try {
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            // Fetch the token for the shop
            $userId = User::where('name', $shop)->pluck('id')->first();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $setting = Setting::with('productdata')->where('user_id', $userId)->first();

            return response()->json([
                'status' => true,
                'message' => 'Setting list retrieved successfully.',
                'setting' => $setting
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Database error when retrieving setting based on token list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Database error occurred.'], 500);
        } catch (\Exception $ex) {
            Log::error('Unexpected error when retrieving setting based on token list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

    public function getUserBasedPlans(Request $request)
    {
        try{
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            // Fetch the token for the shop
            $userId = User::where('name', $shop)->pluck('id')->first();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $plans = Charge::where('user_id', $userId)->pluck('status')->first();
            return response()->json([
                'status' => true,
                'message' => 'Shop active plan retrieved successfully.',
                    'plan' => $plans
            ]);

        } catch (\Illuminate\Database\QueryException $ex) {
            Log::error('Database error when retrieving setting based on token list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Database error occurred.'], 500);
        } catch (\Exception $ex) {
            Log::error('Unexpected error when retrieving setting based on token list', ['exception' => $ex->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred.'], 500);
        }


    }
}
