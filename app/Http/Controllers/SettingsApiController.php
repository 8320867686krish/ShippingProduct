<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsApiController extends Controller
{
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

    private function setMetafield($value, $ownerId, $password, $shop)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $password,
            'Content-Type' => 'application/json',
        ])->post("https://{$shop}/admin/api/2021-10/graphql.json", [
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
                'metafields' => [
                    [
                        'namespace' => "custom",
                        'key' => "shipping_price",
                        'type' => "number_decimal",
                        'value' => $value,
                        'ownerId' => "gid://shopify/Product/{$ownerId}"
                    ]
                ]
            ]
        ]);

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

            $validator = Validator::make($request->all(), [
                'enabled' => 'required|boolean',
                'title' => 'required|string|max:254',
                'shipping_rate' => 'required|numeric|in:1,2',
                'shipping_rate_calculation' => 'required|integer|in:1,2,3',
                'method_name' => 'required|string|max:254',
                'product_shipping_cost' => 'required|boolean',
                'rate_per_item' => 'required|numeric',
                'handling_fee' => 'required|numeric',
                'applicable_countries' => 'required|boolean',
                'countries' => 'required_if:applicable_countries,1',
                'method_if_not_applicable' => 'nullable|boolean',
                'displayed_error_message' => 'nullable|string',
                'show_method_for_admin' => 'required|boolean',
                'min_order_amount' => 'nullable|numeric',
                'max_order_amount' => 'nullable|numeric',
                'productdata.*.checked' => 'boolean',
                'productdata.*.price' => 'required_if:productdata.*.checked,true',
                'productdata.*.product_id' => 'required_if:productdata.*.checked,true',
                'productdata.*.title' => 'required_if:productdata.*.checked,true',
                'productdata.*.value' => 'required_if:productdata.*.checked,true',
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
                'rate_per_item.required' => 'The rate per item is required.',
                'rate_per_item.numeric' => 'The rate per item must be a number.',
                'handling_fee.required' => 'The handling fee is required.',
                'handling_fee.numeric' => 'The handling fee must be a number.',
                'applicable_countries.required' => 'The applicable countries field is required.',
                'countries.required_if' => 'The countries field is required when applicable countries is set to Specific Countries.',
                'method_if_not_applicable.boolean' => 'The method if not applicable field must be true or false.',
                'displayed_error_message.string' => 'The displayed error message must be a string.',
                'show_method_for_admin.required' => 'The show method for admin field is required.',
                'show_method_for_admin.boolean' => 'The show method for admin field must be true or false.',
                'min_order_amount.required' => 'The minimum order amount is required.',
                'min_order_amount.numeric' => 'The minimum order amount must be a number.',
                'max_order_amount.required' => 'The maximum order amount is required.',
                'max_order_amount.numeric' => 'The maximum order amount must be a number.',
                'productdata.array' => 'The product data must be an array.',
                'productdata.*.checked.boolean' => 'The checked value must be true or false.',
                'productdata.*.price.required_if' => 'The price is required when the product is checked.',
                'productdata.*.product_id.required_if' => 'The product ID is required when the product is checked.',
                'productdata.*.title.required_if' => 'The title is required when the product is checked.',
                'productdata.*.value.required_if' => 'The value is required when the product is checked.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $post = $request->input();
            $post['user_id'] = $token['id'];

            // Use updateOrCreate method
            $setting = Setting::updateOrCreate(['user_id' => $token['id']], $post);

            if (null !== $request->input('productdata')) {
                $productValue = 0;
                foreach ($request->input('productdata') as $product) {
                    if (null !== $product) {
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
                            $productValue = $product['value'];
                        } else {
                            Product::where('product_id', $product['product_id'])->where('setting_id', $setting->id)->delete();
                            $productValue = "0";
                        }
                        Log::info('input logs:', ['productValue' => $productValue]);
                        $this->setMetafield($productValue, $product['product_id'], $token['password'], $shop);
                    }
                }
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
}
