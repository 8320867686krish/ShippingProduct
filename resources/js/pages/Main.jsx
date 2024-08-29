import React, { useEffect, useMemo, useState } from 'react';
import { Provider } from '@shopify/app-bridge-react';
import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";
import { useLocation, useNavigate } from 'react-router-dom';
import Routes from '../Routing/Routes';



export default function Main(props)  {
    const navigate = useNavigate();
    const location = useLocation();

    const history = { replace: (path) => navigate(path, { replace: true }) };

    const router = useMemo(
        () => ({ location, history }),
        [location, history],
    );
    
    const config = {
        apiKey: import.meta.env.VITE_SHOPIFY_API_KEY,
        shopOrigin: new URLSearchParams(location.search).get("shop"),
        host: new URLSearchParams(location.search).get("host"),
        forceRedirect: true,
    };
   
    return (
        <Provider config={config} router={router} >
            <Routes {...props} />
        </Provider>
    );
}