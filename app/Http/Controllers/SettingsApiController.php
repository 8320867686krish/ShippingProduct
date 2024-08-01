<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
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
            $token = User::where('name', $shop)->pluck('id')->first();

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
                'applicable_countries' => 'required|numeric',
                'method_if_not_applicable' => 'nullable|boolean',
                'displayed_error_message' => 'nullable|string',
                'show_method_for_admin' => 'required|boolean',
                'min_order_amount' => 'required|numeric',
                'max_order_amount' => 'required|numeric',
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
                'applicable_countries.array' => 'The applicable countries must be an array.',
                'method_if_not_applicable.boolean' => 'The method if not applicable field must be true or false.',
                'displayed_error_message.string' => 'The displayed error message must be a string.',
                'show_method_for_admin.required' => 'The show method for admin field is required.',
                'show_method_for_admin.boolean' => 'The show method for admin field must be true or false.',
                'min_order_amount.required' => 'The minimum order amount is required.',
                'min_order_amount.numeric' => 'The minimum order amount must be a number.',
                'max_order_amount.required' => 'The maximum order amount is required.',
                'max_order_amount.numeric' => 'The maximum order amount must be a number.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $post = $request->input();
            $post['user_id'] = $token;

            // Use updateOrCreate method
            $setting = Setting::updateOrCreate(['id' => $request->input('id')], $post);

            if(null !== $request->input('productdata')) {
                $productData = [];
                foreach($request->input('productdata') as $product){
                    $productData[] = [
                        "user_id" => $token,
                        "setting_id" => $setting->id,
                        "product_id" => $product['id'],
                        "name" => $product['title'],
                        "shipping_price" => $product['value']
                    ];
                }
                Product::where('setting_id', $setting->id)->delete();
                Product::insert($productData);
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
