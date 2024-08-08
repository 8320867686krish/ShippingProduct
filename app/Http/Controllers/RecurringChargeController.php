<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kyon147\LaravelShopify\Facades\ShopifyApp;
use Kyon147\LaravelShopify\Models\Shop;

class RecurringChargeController extends Controller
{
    public function confirmRecurringCharge(Request $request){

        $shop = $request->input('host');
        dd($shop);
        $chargeId = $request->query('charge_id');

        $recurringCharge = $shop->api()->rest('GET', "/admin/recurring_application_charges/{$chargeId}.json");

        if ($recurringCharge['body']['recurring_application_charge']['status'] == 'accepted') {
            // Activate the charge
            $shop->api()->rest('POST', "/admin/recurring_application_charges/{$chargeId}/activate.json");

            return redirect()->route('home')->with('success', 'Recurring charge accepted.');
        }

        return redirect()->route('home')->with('error', 'Recurring charge was not accepted.');
    }
}
