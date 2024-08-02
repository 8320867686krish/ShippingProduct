<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarrierServiceCallbackController extends Controller
{
    public function handleCallback(Request $request)
    {

        $input = $request->input();

        // Log::info('input logs:', ['CallbackInput' => $input]);

        $shopDomain = $request->header()['x-shopify-shop-domain'][0];

        $userId = User::where('name', $shopDomain)->value('id');

        $setting = Setting::with('productdata')->where('user_id', $userId)->first();

        $response = [];

        if ($setting->enabled) {
            $items = collect($input['rate']['items']);

            $newIteam = [];

            foreach ($setting->productdata as $product) {
                $matchedItem = $items->firstWhere('product_id', $product['product_id']);
                if ($matchedItem) {
                    if (@$product['shipping_price']) {
                        $price = $product['shipping_price'];
                    } else {
                        $price = $setting['rate_per_item'];
                    }

                    $quantity = $matchedItem['quantity'];

                    if($setting->shipping_rate == 1){
                        $sum = $quantity * $price;
                    } elseif ($setting->shipping_rate == 2){
                        $sum = $price;
                    }

                    $newIteam[] = $sum;
                }
            }

            if ($setting->shipping_rate_calculation == 1) {
                $totalSum = array_sum($newIteam);
            } elseif($setting->shipping_rate_calculation == 2) {
                $totalSum = max($newIteam);
            } elseif($setting->shipping_rate_calculation == 3) {
                $totalSum = min($newIteam);
            } else {
                $totalSum = $setting->rate_per_item;
            }

            $response['rates'] = [
                'service_name' => $setting->title,
                'service_code' => "RATE200",
                'total_price' => $totalSum, // Convert to cents if needed
                'description' => Carbon::now()->addDay(5)->format('l, d M'),
                'currency' => "INR",
                'min_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
                'max_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
            ];
        }

        return response()->json($response);
    }
}
