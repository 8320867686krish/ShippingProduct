<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private function verifyWebhookInternal($data, $hmacHeader)
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, env('SHOPIFY_API_SECRET'), true));
        return hash_equals($calculatedHmac, $hmacHeader);
    }

    private function getShopAccessToken($shopDomain)
    {
        // Retrieve the access token from your database or wherever you store it
        $shop = User::where('name', $shopDomain)->first();
        return $shop ? $shop : null;
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

    public function handleAppSubscriptions(Request $request)
    {
        Log::info('Received app_subscriptions webhook', $request->all());
    }

    private function getProductMetafields($productId, $accessToken, $shopDomain)
    {
        $apiVersion = config('services.shopify.api_version');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shopDomain}/admin/api/{$apiVersion}/products/{$productId}/metafields.json");

        if ($response->successful()) {
            return $response->json()['metafields'];
        } else {
            Log::error('Error fetching metafields: ' . $response->body());
            return [];
        }
    }

    public function handleProductUpdateWebhook(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        if (!$this->verifyWebhookInternal($data, $hmacHeader)) {
            Log::error('Shopify webhook verification failed. handleProductUpdateWebhook');
            return null;
        }

        $productId = $request->input('id');
        $productTitle = $request->input('title');

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $accessToken = $this->getShopAccessToken($shopDomain);

        $setting = Setting::where('user_id', $accessToken['id'])->value('id');

        $metafields = $this->getProductMetafields($productId, $accessToken['password'], $shopDomain);

        if (!empty($metafields) && !empty($setting)) {
            foreach ($metafields as $metafield) {
                Log::info('loop metafields', ['metafields' => $metafield]);
                Log::info('loop owner_id', ['owner_id' => $metafield['owner_id']]);

                if ($metafield['namespace'] === 'custom' && $metafield['key'] === 'shipping_price') {
                    $productData = [
                        "user_id" => $accessToken['id'],
                        "setting_id" => $setting,
                        "product_id" => $metafield['owner_id'],
                        "title" => $productTitle,
                        "value" => $metafield['value'],
                        "checked" => 1
                    ];

                    if ($metafield['value'] <= 0 || $metafield['value'] == null) {
                        Log::info('loop owner_id', [$metafield['owner_id'] => $metafield['value']]);
                    } else {
                        Product::updateOrCreate(['product_id' => $metafield['owner_id'], 'setting_id' => $setting], $productData);
                    }
                }
            }
        }

        Log::info('metafields', ['metafields' => $metafields]);

        return response()->json(['message' => 'Webhook processed successfully', 'metafields' => $metafields], 200);
        // return response()->json(['message' => 'Webhook processed successfully', 'metafields' => $metafields], 200);
    }
    public function shopRedact(Request $request)
    {
        Log::info('shop reduct.');
    }
}
