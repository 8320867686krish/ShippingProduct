<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarrierServiceCallbackController extends Controller
{
    public function handleCallback(Request $request){
        $input = $request->input();

        Log::info('input logs:', ['CallbackInput' => $input]);
    }
}
