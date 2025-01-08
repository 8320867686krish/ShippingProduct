<?php

namespace App\Http\Controllers;

use App\Jobs\ProductStoreMetafieldJob;
use App\Jobs\SendEmailJob;
use App\Mail\UninstallEmail;
use App\Models\Charge;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    private function verifyWebhookInternal($data, $hmacHeader)
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, config('shopify-app.api_secret'), true));
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

        ProductStoreMetafieldJob::dispatch($productId, $productTitle, $shopDomain);

        return response()->json(['message' => 'Webhook processed successfully'], 200);
        // return response()->json(['message' => 'Webhook processed successfully', 'metafields' => $metafields], 200);
    }

    public function shopRedact(Request $request)
    {
        Log::info('shop reduct.');
    }

    public function handleAppSubscriptions(Request $request)
    {
        try {
            $hmacHeader = $request->header('x-shopify-hmac-sha256', '');
            $data = $request->getContent();
            $verified = $this->verifyWebhookInternal($data, $hmacHeader);

            Log::info("verified", ['verified' => $verified]);

            if ($verified) {
                $shopDomain = $request->header('x-shopify-shop-domain');

                $user = User::where('name', $shopDomain)->first();

                if ($user) {
                    $charge = $request->all()['app_subscription'];

                    $chargeId = explode('/', $charge['admin_graphql_api_id']);

                    Log::info("Call AppSubscriptionsJob", ['chargeId' => $chargeId]);

                    if ($charge['status'] !== "CANCELLED") {
                        Charge::updateOrCreate(['user_id' => $user['id']], [
                            'charge_id' => $chargeId[4],
                            'status' => $charge['status'],
                            'name' => $charge['name'],
                            'type' => '',
                            'price' => "10.00",
                            'user_id' => $user['id'],
                        ]);
                    } else {
                        Log::error('CANCELLED plans', $request->all());
                    }
                } else {
                    Log::warning('User not found for shop domain: ' . $shopDomain);
                }

                Log::info("Shopify Subscriptions request");
                return response()->json(['status' => 'success'], 200);
            } else {
                Log::info("Shopify Subscriptions fail request");
                return response()->json(['status' => 'error'], 401);
            }
        } catch (Throwable $e) {
            Log::error("Error processing shopUpdate webhook: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    public function handleUninstallWebhook(Request $request)
    {
        try {
            $hmacHeader = $request->header('x-shopify-hmac-sha256', '');
            $data = $request->getContent();
            $verified = $this->verifyWebhookInternal($data, $hmacHeader);

            Log::info('Shopify Uninstall Webhook Received', $request->all());

            $reqInput = $request->input();

            Log::info("verified", ['verified' => $verified]);

            if ($verified) {
                $shopDomain = $request->header('x-shopify-shop-domain');

                $user = User::where('name', $shopDomain)->first();

                if ($user) {
                    $user->password = "";
                    $user->isInstall = 0;
                    $user->save();

                    Product::where('user_id', $user->id)->delete();
                    Setting::where('user_id', $user->id)->delete();
                    Charge::where('user_id', $user->id)->delete();

                    $emailData = [
                        "to" => $reqInput['email'] ?? "sanjay@meetanshi.com",
                        'name' => $reqInput['name'] ?? '',
                        'shopDomain' => $reqInput['domain'],
                    ];

                    Log::info('User email data:', ['emailData' => $emailData]);

                    // SendEmailJob::dispatch($emailData, UninstallEmail::class);
                } else {
                    Log::warning('User not found for shop domain: ' . $shopDomain);
                }

                Log::info("Uninstall Webhook request");
                return response()->json(['status' => 'success'], 200);
            } else {
                Log::info("Uninstall Webhook fail request");
                return response()->json(['status' => 'error'], 401);
            }
        } catch (Throwable $e) {
            Log::error("Error processing shopUpdate webhook: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    public function callback(Request $request)
    {
        $query = $request->query();
        $shop= $query['shop'] ?? "";
        $hmac = $query['hmac'] ?? "";
        unset($query['hmac']);
        ksort($query);
        $message = http_build_query($query);
        $calculatedHmac = hash_hmac('sha256', $message, '41e9c77adb191749af82646f75467bd6');

        if (!hash_equals($hmac, $calculatedHmac)) {
            abort(403, 'Invalid HMAC');
        }

        // Step 2: Exchange Authorization Code for Access Token
        $response = Http::post("https://{$query['shop']}/admin/oauth/access_token", [
            'client_id' => '5aab428e38ed7c350a16664477d914f9',
            'client_secret' => '41e9c77adb191749af82646f75467bd6',
            'code' => $query['code'],
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];
            $shop_data = User::where('name', $shop)->first();
            $shop_data->password = $accessToken;
            $shop_data->needs_update = 0;
            $shop_data->save();
            $redirect_url = "https://".$shop."/admin/apps/".env('SHOPIFY_APP');
            return redirect(  $redirect_url );

        }

       // return response()->json(['error' => 'Failed to get access token'], 400);
    }
}
