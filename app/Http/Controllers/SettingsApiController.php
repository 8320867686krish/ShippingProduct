<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Settings;
use Illuminate\Support\Facades\Validator;

class SettingsApiController extends Controller
{
    public function store(Request $request)
    {
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

        $settings = Setting::create($request->all());

        return response()->json($settings, 201);
    }
}
