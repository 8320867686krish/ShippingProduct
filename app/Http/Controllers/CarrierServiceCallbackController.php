<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarrierServiceCallbackController extends Controller
{
    public function handleCallback(Request $request){
        $input = $request->input();

        Log::info('input logs:', ['CallbackInput' => $input]);

        $response['rates'] = [
            'service_name' => "Test Service",
            'service_code' => "RATE200",
            'total_price' => "5000", // Convert to cents if needed
            'description' => Carbon::now()->addDay(5)->format('l, d M'),
            'currency' => "INR",
            'min_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
            'max_delivery_date' => Carbon::now()->addDay(3)->toIso8601String(),
        ];

        return response()->json($response);
    }
}
