import React, { useState, useCallback, useEffect } from 'react'
import axios from 'axios';
import { Redirect } from '@shopify/app-bridge/actions';

import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";

const SHOPIFY_API_KEY = import.meta.env.VITE_SHOPIFY_API_KEY;
const apiCommonURL = import.meta.env.VITE_COMMON_API_URL;

function Products(props) {

    useEffect(() => {
        const app = createApp({
            apiKey: SHOPIFY_API_KEY,
            host: props.host
        });
        const redirect = Redirect.create(app);
        const name = 'meetanshi-shipping-per-product';
        redirect.dispatch(
            Redirect.Action.ADMIN_PATH,
            `/charges/${name}/pricing_plans`
        );
    }, [])


    return (
        <div>sdfsdf</div>
    )
}

export default Products


