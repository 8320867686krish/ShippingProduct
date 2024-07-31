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
        $sortOrder = $request->input('sortOrder', 'asc');
        $sortBy = $request->input('sortBy', 'title');

        $sortKeyMap = [
            'price' => 'PRICE',
            'bestselling' => 'BEST_SELLING',
            'createdat' => 'CREATED_AT',
            'updatedat' => 'UPDATED_AT',
            'id' => 'ID',
            'title' => 'TITLE'
        ];

        $normalizedSortBy = str_replace('_', '', strtolower($sortBy));

        Log::info('Normalized sortBy:', ['normalizedSortBy' => $normalizedSortBy]);

        $sortKey = isset($sortKeyMap[$normalizedSortBy]) ? $sortKeyMap[$normalizedSortBy] : 'TITLE';
        Log::info('sortKey:', ['sortKey' => $sortKey]);


        $requestedFields = $request->input('fields', ['id', 'title']);
        $variantLimit = $request->input('variantLimit', 1);
        $variantFields = $request->input('variantFields', ['title', 'price']);
        $imageLimit = $request->input('imageLimit', 1);
        $imageFields = $request->input('imageFields', ['id', 'src']);

        $query = '{ products(';

        if (isset($endCursor)) {
            $query .= 'first: ' . $first . ', after: "' . $endCursor . '", ';
        } elseif (isset($startCursor)) {
            $query .= 'last: ' . $last . ', before: "' . $startCursor . '", ';
        } else {
            $query .= 'first: ' . $first . ', ';
        }

        $query .= 'sortKey: ' . $sortKey . ', ';
        $query .= 'reverse: ' . ($sortOrder === 'desc' ? 'true' : 'false');

        $query .= '){ edges { node {';

        foreach ($requestedFields as $field) {
            if ($field !== 'variants' && $field !== 'images') {
                $query .= $field . ' ';
            }
        }

        if (in_array('variants', $requestedFields)) {
            $query .= 'variants(first: ' . $variantLimit . ') { edges { node { ';
            foreach ($variantFields as $variantField) {
                $query .= $variantField . ' ';
            }
            $query .= '} } } ';
        }

        if (in_array('images', $requestedFields)) {
            $query .= 'images(first: ' . $imageLimit . ') { edges { node { ';
            foreach ($imageFields as $imageField) {
                $query .= $imageField . ' ';
            }
            $query .= '} } } ';
        }

        $query .= '} } pageInfo { hasNextPage hasPreviousPage endCursor startCursor } } }';

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
        
        usort($productsData, function ($a, $b) {
            $priceA = isset($a['node']['variants']['edges'][0]['node']['price']) ? floatval($a['node']['variants']['edges'][0]['node']['price']) : PHP_FLOAT_MAX;
            $priceB = isset($b['node']['variants']['edges'][0]['node']['price']) ? floatval($b['node']['variants']['edges'][0]['node']['price']) : PHP_FLOAT_MAX;
    
            return $priceA <=> $priceB;
        });
    
        if ($sortOrder === 'desc') {
            $productsData = array_reverse($productsData);
        }
        return response()->json($productsData);
    }

    public function getCountryList(Request $request)
    {
        try {
            // Retrieve the Shopify session
            // $shop = $request->attributes->get('shopifySession');
            // // $shop = "krishnalaravel-test.myshopify.com";

            // if (!$shop) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Token not provided.'
            //     ], 400);
            // }

            // // Fetch the token for the shop
            // $token = User::where('name', $shop)->pluck('password')->first();

            // if (!$token) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'User not found.'
            //     ], 404);
            // }

            $countriesArray = CountryState::getCountries();

            // Initialize an empty array to hold the formatted data
            $countries = [];

            // Iterate over the associative array and format it into an array of objects
            foreach ($countriesArray as $isoCode => $name) {
                $countries[] = (object) [
                    'code' => $isoCode,
                    'name' => $name,
                    'nameCode' => $name . " " . "(" . $isoCode . ")"
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

