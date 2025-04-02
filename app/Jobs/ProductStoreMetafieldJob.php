<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductStoreMetafieldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;
    protected $productTitle;
    protected $shopDomain;

    /**
     * Create a new job instance.
     */
    public function __construct($productId, $productTitle, $shopDomain)
    {
        $this->productId = $productId;
        $this->productTitle = $productTitle;
        $this->shopDomain = $shopDomain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accessToken = $this->getShopAccessToken($this->shopDomain);

        $setting = Setting::where('user_id', $accessToken['id'])->value('id');

        $metafields = $this->getProductMetafields($this->productId, $accessToken['password'], $this->shopDomain);

        if (!empty($metafields) && !empty($setting)) {
            foreach ($metafields as $metafield) {

                if ($metafield['namespace'] === 'custom' && $metafield['key'] === 'shipping_price') {
                    $productData = [
                        "user_id" => $accessToken['id'],
                        "setting_id" => $setting,
                        "product_id" => $metafield['owner_id'],
                        "title" => $this->productTitle,
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
        
    }

    private function getShopAccessToken($shopDomain)
    {
        // Retrieve the access token from your database or wherever you store it
        $shop = User::where('name', $shopDomain)->first();
        return $shop ? $shop : null;
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
}
