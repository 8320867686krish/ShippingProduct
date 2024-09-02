import React, { useState, useCallback, useEffect } from 'react'
import axios from 'axios';

import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";

const SHOPIFY_API_KEY = import.meta.env.VITE_SHOPIFY_API_KEY;
const apiCommonURL = import.meta.env.VITE_COMMON_API_URL;

function Products(props) {
   

    const getPlans = async () => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: props.host
            });
            const token = await getSessionToken(app);
            const response = await axios.get(`${apiCommonURL}/api/plans`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            let status = response.data.plan?.toLowerCase();
            console.log(response.data)

            if (status !== "active") {
                const name = 'meetanshi-shipping-per-product';
                window.top.location.href = `https://admin.shopify.com/charges/${name}/pricing_plans`;
            }

        } catch (error) {
            console.error("Error fetching plans:", error);
        }
        finally {
          
        }
    }
    useEffect(() => {
        getPlans();
    }, [])
 
    
    return (
      <div></div>
    )
}

export default Products


