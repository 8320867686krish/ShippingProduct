<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mgcodeur\CurrencyConverter\Facades\CurrencyConverter;

class CarrierServiceCallbackController extends Controller
{
    public function handleCallback(Request $request)
    {
        $input = $request->input();

        Log::info('input logs:', ['CallbackInput' => $input]);

        $shopDomain = $request->header()['x-shopify-shop-domain'][0];

        $userId = User::where('name', $shopDomain)->value('id');

        $reqCurrency = $input['rate']['currency'];

        $items = $input['rate']['items'];

        $totalPrice = array_reduce($items, function ($carry, $item) {
            $itemTotal = ($item['price'] * $item['quantity']) / 100;
            return $carry + $itemTotal;
        }, 0);

        $setting = Setting::with('productdata')->where('user_id', $userId)->first();

        $response = [];

        if ($setting->enabled) {
            $destinationCountryName = $input['rate']['destination']['country'];

            if ($setting->applicable_countries == 1) {
                if (null !== $setting->countries) {
                    if (!in_array($destinationCountryName, $setting->countries)) {
                        return response()->json($response);
                    }
                }
            }

            $jsonFileData = file_get_contents(public_path('countries.json'));
            $data = json_decode($jsonFileData, true);
            $countries = $data['countries']['country'];
            $country = collect($countries)->firstWhere('countryCode', $destinationCountryName);

            if ($country) {
                $currencyCode = $country['currencyCode'];
            } else {
                $currencyCode = "INR";
            }

            $items = $input['rate']['items'];

            $newIteam = [];

            $settingProduct = collect($setting->productdata);

            $itemIdArray = $settingProduct->pluck('product_id')->toArray();

            Log::info('input logs:', ['settingProduct' => $itemIdArray]);
            Log::info('input logs:', ['settingProduct' => $currencyCode]);

            foreach ($items as $item) {
                $matchedItem = $settingProduct->firstWhere('product_id', $item['product_id']);

                if ($setting->product_shipping_cost) {
                    $price = $setting['rate_per_item'];
                } else {
                    if (in_array($item['product_id'], $itemIdArray) && $matchedItem) {
                        if ($matchedItem['value'] != null) {
                            $price = $matchedItem['value'];
                        } else {
                            $price = $setting['rate_per_item'];
                        }
                    } else {
                        $price = $setting['rate_per_item'];
                    }
                }

                Log::info('input logs:', ['price' => $price]);
                $quantity = $item['quantity'];

                if ($setting->shipping_rate == 1) {
                    $sum = $quantity * $price;
                } elseif ($setting->shipping_rate == 2) {
                    $sum = $price;
                }
                $newIteam[] = $sum;
            }
            Log::info('input logs:', ['newIteam' => $newIteam]);
            if ($setting->shipping_rate_calculation == 1) {
                $totalSum = array_sum($newIteam);
            } elseif ($setting->shipping_rate_calculation == 2) {
                $totalSum = max($newIteam);
            } elseif ($setting->shipping_rate_calculation == 3) {
                $totalSum = min($newIteam);
            } else {
                $totalSum = $setting->rate_per_item;
            }
            Log::info('input logs:', ['totalSum' => $totalSum]);

            $finalRatePrice = $totalSum + ($setting->handling_fee ?? 0.00);

            if (!is_null($setting->min_order_amount) && !is_null($setting->max_order_amount) && $setting->min_order_amount != 0 && $setting->max_order_amount != 0) {
                $isWithinOrderRange = $setting->min_order_amount <= $totalPrice && $totalPrice <= $setting->max_order_amount;

                Log::info('input logs:', ['isWithinOrderRange' => $isWithinOrderRange]);
                Log::info('input logs:', ['totalPrice' => $totalPrice]);

                if(!$isWithinOrderRange){
                    return response()->json($response);
                }
            }

            Log::info('input logs:', ['finalRatePrice' => $finalRatePrice]);

            $convertedAmount = CurrencyConverter::convert($finalRatePrice)->from($reqCurrency)->to($currencyCode)->get();

            Log::info('input logs:', ['convertedAmount' => $convertedAmount]);

            // dd($convertedAmount);

            $response['rates'] = [
                'service_name' => $setting->title,
                'service_code' => "RATE200",
                'total_price' => $convertedAmount * 100, // Convert to cents if needed
                'description' => $setting->method_name,
                'currency' => $currencyCode
                // 'min_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
                // 'max_delivery_date' => Carbon::now()->addDay(5)->toIso8601String(),
            ];
        }

        return response()->json($response);
    }
}
