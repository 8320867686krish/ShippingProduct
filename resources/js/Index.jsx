import { AppProvider } from '@shopify/polaris';
import React, { useState, useEffect } from 'react';
import '@shopify/polaris/build/esm/styles.css';
import translations from "@shopify/polaris/locales/en.json";
import { BrowserRouter } from 'react-router-dom';
import { Frame } from '@shopify/polaris';
import { getSessionToken } from "@shopify/app-bridge-utils";
import Routing from './Routing/Routes';

export default function Index(props) {

    return (
        <BrowserRouter>
            <AppProvider i18n={translations}>
                <Frame>
                   <Routing/>
                </Frame>
            </AppProvider>
        </BrowserRouter>
    );
}
