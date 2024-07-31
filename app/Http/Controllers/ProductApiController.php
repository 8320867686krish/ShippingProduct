<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use DougSisk\CountryState\CountryState;
use Illuminate\Http\Client\RequestException;

class ApiController extends Controller
{
    public function products(Request $request)
    {
        $shopName = $request->input('shop');
        $shopExist = User::where( 'name', $shopName )->first();

        if (!$shopExist) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $token = $shopExist->password;
        $first = $request->input('first', 5);
        $last = $request->input('last', 5);
       
        $endCursor = $request->input('endCursor', null);
        $startCursor = $request->input('startCursor', null);

        $query = '{ products(';

        if (isset($endCursor)) {
            $query .= 'first: ' . $first . ', after: "' . $endCursor . '", ';
        } elseif (isset($startCursor)) {
            $query .= 'last: ' . $last . ', before: "' . $startCursor . '", ';
        } else {
            $query .= 'first: ' . $first . ', ';
        }

        $query .= ') { 
            edges { 
              node { 
                id 
                title 
                variants(first: 1) { 
                    edges { 
                      node { 
                        id 
                        title 
                        price 
                        sku 
                        position                        
                      } 
                    } 
                  } 
                  images(first: 1) { 
                    edges { 
                      node { 
                      src 
                         
                      } 
                    } 
                  } 
 
              } 
             
            } 
            pageInfo { 
                startCursor 
                endCursor 
                hasNextPage 
                hasPreviousPage 
              } 
          } 
        } 
    '; 

        Log::info('query:', ['query' => $query]);

        $response = Http::withHeaders( [
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ] )->post( 'https://' . $shopName . '/admin/api/2023-10/graphql.json', [
            'query' => $query,
            // 'variables' => $variables, 
        ] );

        if ( $response->failed() ) {
            return [ 'data' => $response->json() ];
        } else {
            return [ 'data' => $response->json() ];
        }

        if (isset($response['data']['errors'])) {
            return response()->json(['error' => 'Failed to fetch products', 'details' => $response['data']['errors']], 500);
        }

        $productsData = $response['data']['data']['products']['edges'];
        
        return response()->json($productsData);
    }

    public function getCountryList(Request $request)
    {
        try {
            Retrieve the Shopify session
            $shop = $request->attributes->get('shopifySession');
            // $shop = "krishnalaravel-test.myshopify.com";

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            // Fetch the token for the shop
            $token = User::where('name', $shop)->pluck('password')->first();

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $countriesArray = CountryState::getCountries();

            // Initialize an empty array to hold the formatted data
            $countries = [];

            // Iterate over the associative array and format it into an array of objects
            foreach ($countriesArray as $isoCode => $name) {
                $countries[] = (object) [
                    'code' => $isoCode,
                    'name' => $name,
                    // 'nameCode' => $name . " " . "(" . $isoCode . ")"
                ];
            }

            return response()->json(['status' => true, 'message' => 'countries list retrieved successfully.', 'countries' => $countries]);
        } catch (RequestException $e) {
            // Handle request-specific exceptions
            Log::error('HTTP request error', ['exception' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred:']);
        } catch (\Throwable $th) {
            Log::error('Unexpected error', ['exception' => $th->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred:']);
        }
    }
}

