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
    private function buildGraphQLQuery($inputs)
    {
        $first = $inputs['first'] ?? 10;
        $last = $inputs['last'] ?? 10;
        $endCursor = $inputs['endCursor'] ?? null;
        $startCursor = $inputs['startCursor'] ?? null;

        $pagination = isset($endCursor) ? "first: $first, after: \"$endCursor\"" : (isset($startCursor) ? "last: $last, before: \"$startCursor\"" : "first: $first");

        return <<<GRAPHQL
        {
            products($pagination) {
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
        GRAPHQL;
    }

    // public function products(Request $request)
    // {
    //     $shop = $request->attributes->get('shopifySession', "jaypal-demo.myshopify.com");

    //     if (!$shop) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Token not provided.'
    //         ], 400);
    //     }

    //     // Fetch the token for the shop
    //     $token = User::where('name', $shop)->pluck('password')->first();

    //     if (!$token) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found.'
    //         ], 404);
    //     }

    //     $validated = $request->validate([
    //         'first' => 'integer|min:1|max:100',
    //         'last' => 'integer|min:1|max:100',
    //         'endCursor' => 'nullable|string',
    //         'startCursor' => 'nullable|string',
    //     ]);

    //     $query = $this->buildGraphQLQuery($validated);

    //     // Log::info('query:', ['query' => $query]);

    //     $response = Http::withHeaders([
    //         'X-Shopify-Access-Token' => $token,
    //         'Content-Type' => 'application/json',
    //     ])->post('https://' . $shop . "/admin/api/2024-01/graphql.json", [
    //         'query' => $query,
    //     ]);


    //     if (isset($response['data']['errors'])) {
    //         return response()->json(['error' => 'Failed to fetch products', 'details' => $response['data']['errors']], 500);
    //     }

    //     $jsonResponse = $response->json();

    //     // Prepare the response data
    //     $data = [];
    //     if (isset($jsonResponse['data'])) {
    //         $collectionsArray = [];
    //         foreach ($jsonResponse['data']['products']['edges'] as $value) {
    //             $product = $value['node'];
    //             // Fetch the first variant's price
    //             $price = null;
    //             if (isset($product['variants']['edges'][0]['node']['price'])) {
    //                 $price = $product['variants']['edges'][0]['node']['price'];
    //             }
    //             $itemArray = [
    //                 'id' => str_replace('gid://shopify/Product/', '', $product['id']),
    //                 'title' => ucfirst($product['title']),
    //                 'image' => isset($product['images']['edges'][0]['node']['src']) ? $product['images']['edges'][0]['node']['src'] : null,
    //                 'price' => $price
    //             ];
    //             $collectionsArray[] = $itemArray;
    //         }

    //         $data['products'] = $collectionsArray;
    //         $data['hasNextPage'] = $jsonResponse['data']['products']['pageInfo']['hasNextPage'];
    //         $data['hasPreviousPage'] = $jsonResponse['data']['products']['pageInfo']['hasPreviousPage'];
    //         $data['endCursor'] = $jsonResponse['data']['products']['pageInfo']['endCursor'];
    //         $data['startCursor'] = $jsonResponse['data']['products']['pageInfo']['startCursor'];
    //     }

    //     // Return the JSON response
    //     return response()->json($data);
    // }

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

            // Determine the query string based on cursor parameters
            if (isset($post['endCursor'])) {
                $querystring = 'first: 10, after: "' . $post['endCursor'] . '"';
            } elseif (isset($post['startCursor'])) {
                $querystring = 'last: 10, before: "' . $post['startCursor'] . '"';
            } else {
                $querystring = 'first: 10';
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
                                        altText
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
                    if (isset($product['variants']['edges'][0]['node']['price'])) {
                        $price = $product['variants']['edges'][0]['node']['price'];
                    }
                    $itemArray = [
                        'id' => str_replace('gid://shopify/Product/', '', $product['id']),
                        'title' => ucfirst($product['title']),
                        'image' => isset($product['images']['edges'][0]['node']['originalSrc']) ? $product['images']['edges'][0]['node']['originalSrc'] : null,
                        'price' => $price
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
            $shop = $request->attributes->get('shopifySession');
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
