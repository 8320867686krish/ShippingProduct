<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function Index(Request $request)
    {
        $post = $request->input();
        $shop = $request->input('shop');
        $host = $request->input('host');

        $shopName = $post['shop'];
        $token = User::where('name', $shopName)->first();
        $this->setMetaFiled($token);

        return view('welcome', compact('shop', 'host'));
    }

    public function common(Request $request)
    {
        $shop = $request->input('shop');
        $host = $request->input('host');
        return view('welcome', compact('shop', 'host'));
    }
    public function setMetaFiled($shop){
        $url = "https://".$shop['name']."/admin/api/2021-10/graphql.json";

        $query = '
        mutation MetafieldDefinitionCreateMutation($input: MetafieldDefinitionInput!) {
            metafieldDefinitionCreate(definition: $input) {
                userErrors {
                    code
                    message
                    field
                }
            }
        }';
        
        $variables = [
            'input' => [
                'ownerType' => 'PRODUCT',
                'namespace' => 'custom',
                'key' => 'shipping_price',
                'type' => 'number_decimal',
                'validations' => [],
                'name' => 'Shipping Price',
                'description' => '',
                'pin' => true,
                'useAsCollectionCondition' => false
            ]
        ];
        
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop['password'],
        ])->post($url, [
            'query' => $query,
            'variables' => $variables
        ]);
        
    
     
      
        return $response->json();

    }
}
