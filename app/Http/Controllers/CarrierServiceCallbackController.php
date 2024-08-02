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

            if ($setting->applicable_countries == 1) {
                $destinationCountryName = $input['rate']['destination']['country'];
                if(null !== $setting->countries){
                    if (!in_array($destinationCountryName, $setting->countries)) {
                        return response()->json($response);
                    }
                }
            }

            $items = $input['rate']['items'];

            $newIteam = [];

            $settingProduct = collect($setting->productdata);

            $itemIdArray = $settingProduct->pluck('product_id')->toArray();

            Log::info('input logs:', ['settingProduct' => $itemIdArray]);

            foreach ($items as $item) {
                $matchedItem = $settingProduct->firstWhere('product_id', $item['product_id']);

                if (in_array($item['product_id'], $itemIdArray) && $matchedItem) {
                    if ($matchedItem['value'] != null) {
                        $price = $matchedItem['value'];
                    } else {
                        $price = $setting['rate_per_item'];
                    }
                } else {
                    $price = $setting['rate_per_item'];
                }

                $quantity = $item['quantity'];

                if ($setting->shipping_rate == 1) {
                    $sum = $quantity * $price;
                } elseif ($setting->shipping_rate == 2) {
                    $sum = $price;
                }
                $newIteam[] = $sum;
            }

            if ($setting->shipping_rate_calculation == 1) {
                $totalSum = array_sum($newIteam);
            } elseif ($setting->shipping_rate_calculation == 2) {
                $totalSum = max($newIteam);
            } elseif ($setting->shipping_rate_calculation == 3) {
                $totalSum = min($newIteam);
            } else {
                $totalSum = $setting->rate_per_item;
            }

            $finalRatePrice = $totalSum + $setting->handling_fee ?? 0.00;

            $response['rates'] = [
                'service_name' => $setting->title,
                'service_code' => "RATE200",
                'total_price' => $finalRatePrice, // Convert to cents if needed
                'description' => Carbon::now()->addDay(5)->format('l, d M'),
                'currency' => "INR",
                'min_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
                'max_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
            ];
        }

        return response()->json($response);
    }
}
