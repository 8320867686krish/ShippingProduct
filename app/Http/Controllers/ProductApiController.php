<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use CountryState;

use Illuminate\Http\Client\RequestException;

class ProductApiController extends Controller
{
    public function products(Request $request)
    {
        try {
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");
            // $shop = "krishnalaravel-test.myshopify.com";

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided.'
                ], 400);
            }

            $post = $request->input();

            // Retrieve the Shopify access token based on the store name
            $token = User::where('name', $shop)->first();

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $first = $request->input('first', 50);
            $last = $request->input('last', 50);

            // Determine the query string based on cursor parameters
            if (isset($post['endCursor'])) {
                $querystring = 'first:' . $first . ', after: "' . $post['endCursor'] . '"';
            } elseif (isset($post['startCursor'])) {
                $querystring = 'last: ' . $last . ', before: "' . $post['startCursor'] . '"';
            } else {
                $querystring = 'first:' . $first;
            }

            // if (isset($post['query'])) {
            //     $escapedQuery = addslashes($post['query']); // Escape special characters in the query
            //     $queryParam = 'title:' . $escapedQuery . '';
            // }

            // Determine the query parameter
            $queryParam = isset($post['query']) ? 'query:"' . $post['query'] . '"' : '';

            // GraphQL query to fetch products
            $query = <<<GRAPHQL
            {
                products($querystring, sortKey: CREATED_AT, reverse: true, $queryParam) {
                    edges {
                        node {
                            id
                            title
                            metafields(namespace: "custom", first: 1) {
                                edges {
                                    node {
                                        key
                                        value
                                    }
                                }
                            }
                            variants(first: 1) {
                                edges {
                                    node {
                                        id
                                        price
                                    }
                                }
                            }
                            images(first: 1) {
                                edges {
                                    node {
                                        originalSrc
                                    }
                                }
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        endCursor
                        startCursor
                    }
                }
            }
            GRAPHQL;

            // Shopify GraphQL endpoint
            $graphqlEndpoint = "https://$shop/admin/api/2023-07/graphql.json";

            // Headers for Shopify API request
            $customHeaders = [
                'X-Shopify-Access-Token' => $token['password'],
            ];

            // Make HTTP POST request to Shopify GraphQL endpoint
            $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, [
                'query' => $query,
            ]);
            // Parse the JSON response
            $jsonResponse = $response->json();

            // Prepare the response data
            $data = [];
            if (isset($jsonResponse['data'])) {
                $collectionsArray = [];
                foreach ($jsonResponse['data']['products']['edges'] as $value) {
                    $product = $value['node'];
                    // Fetch the first variant's price
                    $price = null;
                    $metafields = "0.00";
                    $checked = 0;
                    if (isset($product['variants']['edges'][0]['node']['price'])) {
                        $price = $product['variants']['edges'][0]['node']['price'];
                    }
                    if (isset($product['metafields']['edges'][0]['node']['value'])) {
                        $metafields = $product['metafields']['edges'][0]['node']['value'];
                        $checked = 1;
                    }

                    $itemArray = [
                        'id' => str_replace('gid://shopify/Product/', '', $product['id']),
                        'title' => ucfirst($product['title']),
                        'image' => isset($product['images']['edges'][0]['node']['originalSrc']) ? $product['images']['edges'][0]['node']['originalSrc'] : null,
                        'price' => $price,
                        'value' => $metafields,
                        'checked' => $checked
                    ];
                    $collectionsArray[] = $itemArray;
                }

                $data['products'] = $collectionsArray;
                $data['hasNextPage'] = $jsonResponse['data']['products']['pageInfo']['hasNextPage'];
                $data['hasPreviousPage'] = $jsonResponse['data']['products']['pageInfo']['hasPreviousPage'];
                $data['endCursor'] = $jsonResponse['data']['products']['pageInfo']['endCursor'];
                $data['startCursor'] = $jsonResponse['data']['products']['pageInfo']['startCursor'];
            }

            // Return the JSON response
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Unexpected get product api error', ['exception' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred:']);
        }
    }

    public function getCountryList(Request $request)
    {
        try {
            // Retrieve the Shopify session
            $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");
            // $shop = "swatipatel.myshopify.com";

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

            $apiVersion = config('services.shopify.api_version');

            $query = <<<'GRAPHQL'
                query AllMarkets($after: String) {
                    markets(first: 250, after: $after) {
                        edges {
                            node {
                                ...MarketSummary
                            }
                        }
                    }
                    }

                    fragment MarketSummary on Market {
                    id
                    name
                    handle
                    active: enabled
                    primary
                    regions(first: 250) {
                        edges {
                        node {
                            id
                            name
                            ... on MarketRegionCountry {
                            code

                            }
                        }
                        }
                    }
                    __typename
                }
                GRAPHQL;

            $customHeaders = [
                'X-Shopify-Access-Token' => $token,
            ];

            $graphqlEndpoint = "https://{$shop}/admin/api/{$apiVersion}/graphql.json";

            $response = Http::withHeaders($customHeaders)->post($graphqlEndpoint, ['query' => $query]);

            $countriesArray = $response->json();

            $countries = [];

            // Iterate over the associative array and format it into an array of objects
            foreach ($countriesArray['data']['markets']['edges'] as $market) {
                if ($market['node']['active']) {
                    foreach ($market['node']['regions']['edges'] as $region) {
                        $country = $region['node'];
                        if (isset($country['code']) && isset($country['name'])) {
                            $countries[] = [
                                'code' => $country['code'],
                                'name' => $country['name']
                            ];
                        }
                    }
                }
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
