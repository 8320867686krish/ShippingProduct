<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class SettingsApiController extends Controller
{
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
}
