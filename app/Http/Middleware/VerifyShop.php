<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        // dd($request->input('embedded'));
        if ($request->input('embedded') == 1) {
            $host = $request->input('host');
            $shop = $request->input('shop');
            $shop_exist = User::where('name', $shop)->first();
            // return $next($request);
            return response()->view('welcome', ['host'=>$host, 'shop_exist'=>$shop_exist, 'shop'=>$shop], 200);
        } else {
            $shop = $request->input('shop');
            if (@$shop) {
                return $next($request);
            }
            $pathToFile = public_path('site/index.html');
            return response()->file($pathToFile);
        }
    }
}
