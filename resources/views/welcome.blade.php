<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \Osiset\ShopifyApp\Util::getShopifyConfig('app_name') }}</title>
    <style>
        .loader {
            border: 8px solid #f3f3f3;
            /* Light grey */
            border-top: 8px solid #3498db;
            /* Blue */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -25px;
            /* Negative half of the width */
            margin-top: -25px;
            /* Negative half of the height */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    @viteReactRefresh
    @vite(['resources/css/app.css'])
</head>

<body>
    <div id="loader" class="loader"></div>

    <div class="app-wrapper">
        <div class="app-content">
            <main role="main">
                <div id="main" data-host="{{ $host }}"></div>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var loader = document.getElementById("loader");
            loader.style.display = "block"; // Show the loader
        });

        // Hide the loader when your content has finished loading (for example, in your app.js)
        window.addEventListener("load", function() {

            var loader = document.getElementById("loader");

            loader.style.display = "none"; // Hide the loader
        });
        var shopUrl = "{{ $shop_exist['name'] }}"; // Shopify shop domain
        var installUrl = "https://" + shopUrl +
            "/admin/oauth/authorize?client_id=5aab428e38ed7c350a16664477d914f9&scope=read_products,write_products&redirect_uri=https://spp.meetanshi.work/callback";
        var needs_update = "{{ $shop_exist['needs_update'] }}";
        console.log(needs_update);
        if (needs_update == 1) {
            window.top.location = installUrl; // Redirect to the installation URL
        }
    </script>
    @vite(['resources/js/app.jsx'])
</body>

</html>
