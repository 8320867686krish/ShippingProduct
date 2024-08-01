<?php

namespace App\Http\Controllers;

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
            $token = User::where('name', $shop)->pluck('password')->first();

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $setting = Setting::all();

            return response()->json([
                'status' => true,
                'message' => 'Setting list retrieved successfully.',
                'settings' => $setting,
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
        $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Token not provided.'
            ], 400);
        }

        // Fetch the token for the shop
        $token = User::where('name', $shop)->pluck('password')->first();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'title' => 'required|string|max:100',
            'shipping_rate' => 'required|string|max:20',
            'shipping_rate_calculation' => 'required|string|max:20',
            'method_name' => 'required|string|max:100',
            'product_shipping_cost' => 'required|boolean',
            'rate_per_item' => 'required|integer',
            'handling_fee' => 'required|integer',
            'applicable_countries' => 'required|string|max:25',
            'method_if_not_applicable' => 'required|boolean',
            'displayed_error_message' => 'required|string',
            'show_method_for_admin' => 'required|boolean',
            'minimum_order_amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $post = $request->input();
        $post['user_id'] = $token['id'];
        $searchCriteria = ['id' => $post['id']];
    // Use updateOrCreate method
        $settings = Setting::updateOrCreate($searchCriteria, $post);

        return response()->json($settings, 201);
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

            $setting = Setting::where('user_id', $userId)->get();

            return response()->json([
                'status' => true,
                'message' => 'Setting list retrieved successfully.',
                'settings' => $setting
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
