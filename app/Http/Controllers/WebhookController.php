<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private function verifyWebhookInternal($data, $hmacHeader)
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, self::CLIENT_SECRET, true));
        return hash_equals($calculatedHmac, $hmacHeader);
    }

    public function customersUpdate(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $utf8 = utf8_encode($data);
        $txt = json_decode($utf8, true);

        $verified = $this->verifyWebhookInternal($data, $hmacHeader);

        if ($verified) {
            Log::info("customer update request");
            return response()->json(['status' => 'success'], 200);
        } else {
            Log::info("customer update fail request");
            return response()->json(['status' => 'error'], 401);
        }
    }

    public function customersDelete(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $utf8 = utf8_encode($data);
        $txt = json_decode($utf8, true);

        $verified = $this->verifyWebhookInternal($data, $hmacHeader);

        if ($verified) {
            Log::info("customer delete request");
            return response()->json(['status' => 'success'], 200);
        } else {
            Log::info("customer delete fail request");
            return response()->json(['status' => 'error'], 401);
        }
    }

    public function shopUpdate(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $utf8 = utf8_encode($data);
        $txt = json_decode($utf8, true);

        $verified = $this->verifyWebhookInternal($data, $hmacHeader);

        if ($verified) {
            Log::info("shop update request");
            return response()->json(['status' => 'success'], 200);
        } else {
            Log::info("shop update fail request");
            return response()->json(['status' => 'error'], 401);
        }
    }

    public function handleAppSubscriptions(Request $request){
        Log::info('Received app_subscriptions webhook', $request->all());
    }
}
