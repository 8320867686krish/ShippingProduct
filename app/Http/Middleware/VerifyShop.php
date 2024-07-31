<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyShop
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
        if ($request->input('embedded') == 1) {
            $shop = $request->input('host');
            return response()->view('welcome', ['host' => $shop], 200);

        }
        return $next($request);
    }
}
