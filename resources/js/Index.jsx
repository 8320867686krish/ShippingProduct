import { AppProvider } from '@shopify/polaris';
import React, { useState, useEffect } from 'react';
import '@shopify/polaris/build/esm/styles.css';
// import '../../public/css/style.css';
import translations from "@shopify/polaris/locales/en.json";
import { BrowserRouter } from 'react-router-dom';
// import Main from './Pages/Main'
import Products from './pages/Products';
import { Frame } from '@shopify/polaris';
import { getSessionToken } from "@shopify/app-bridge-utils";

export default function Index(props) {

    return (
        <BrowserRouter>
            <AppProvider i18n={translations}>
                <Frame>
                    <Products {...props} />
                </Frame>
            </AppProvider>
        </BrowserRouter>
    );
}
