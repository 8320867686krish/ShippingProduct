<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Throwable;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        $secret = env('SHOPIFY_API_SECRET');

        if (!$token) {
            return response()->json(["status" => false, 'error' => 'Unauthorized'], 401);
        }

        if ($token && strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        try {
            // Attempt to decode the token
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            $shopUrl = $decoded->dest;
            $explode = explode('//', $shopUrl);
            // Add the decoded token data to request attributes
            $request->attributes->add(['shopifySession' => $explode[1]]);

            // Proceed to the next middleware or route handler
            return $next($request);
        } catch (ExpiredException $e) {
            // Token has expired
            return response()->json(["status" => false, 'error' => 'Token expired'], 401);
        } catch (SignatureInvalidException $e) {
            // Token signature is invalid
            return response()->json(["status" => false, 'error' => 'Invalid token'], 401);
        } catch (BeforeValidException $e) {
            // Token is not yet valid
            return response()->json(["status" => false, 'error' => 'Token not yet valid'], 401);
        } catch (Throwable $e) {
            // Other exceptions
            return response()->json(["status" => false, 'error' => 'Unauthorized'], 401);
        }
    }
}
