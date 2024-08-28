import React, { useEffect, useState } from 'react'
import {
    LegacyCard,
    Page,
    Text,
    Button,
    Divider,
    Grid,
    Link,
    Icon
} from '@shopify/polaris';
import {
    ShareIcon, StarFilledIcon
} from '@shopify/polaris-icons';


function Support() {
    const handleClick = () => {
        (function (d, src, c) {
            const t = d.scripts[d.scripts.length - 1];
            const s = d.createElement('script');
            s.id = 'la_x2s6df8d';
            s.defer = true;
            s.src = src;
            s.onload = s.onreadystatechange = function () {
                const rs = this.readyState;
                if (rs && (rs !== 'complete') && (rs !== 'loaded')) {
                    return;
                }
                c(this);
            };
            t.parentElement.insertBefore(s, t.nextSibling);
        })(document,
            'https://helpdesk.meetanshi.com/scripts/track.js',
            function (e) {
                window.LiveAgent.createButton('eb8cc755', e);
            });
    };

    const handleButtonAiContente = () => {
        window.open('https://apps.shopify.com/ai-product-description-articles');
    };
    const handleButtonPdf = () => {
        window.open('https://apps.shopify.com/meetanshi-pdf-catalog');
    };
    const handleButtonCollections = () => {
        window.open('https://apps.shopify.com/collections-import-export');
    };
    const handleButtonZoom = () => {
        window.open('https://meet.meetanshi.com/meet/support');
    };
    const handleButtonEmail = () => {
        window.open('mailto:example@gmail.com');
    };
    return (
        <Page title="Help">
            <Grid>
                <Grid.Cell columnSpan={{ xs: 8, sm: 3, md: 3, lg: 8, xl: 8 }}>
                    <LegacyCard sectioned title="Contact our support">
                        <Text variant="headingXs" as="h6">
                            Get instant answers to your queries and assistance to customize the app tp suit your theme.
                        </Text>
                        <div style={{ marginTop: "2%", display: "flex", gap: "5px", alignItems: "center" }}>
                            <Button variant="primary"  onClick={handleClick}>Chat with Us</Button>
                            <Button variant="tertiary" onClick={handleButtonEmail} >
                                Email to help@meetanshi.com
                            </Button>
                        </div>
                    </LegacyCard>

                    <LegacyCard sectioned title="Free setup assistance">
                        <Text variant="headingXs" as="h6">
                            if you need support to get started or to setup offers, please reach out to our support team.
                        </Text>
                        <div style={{ marginTop: "2%", }}>
                            <Button icon={ShareIcon} onClick={handleButtonZoom} >Schedual a meeting</Button>
                        </div>
                    </LegacyCard>

                    <LegacyCard sectioned title="Checkout the user guide">
                        <Text variant="headingXs" as="h6">
                            our user guide has step by step instructions on how to setup and use the app.
                        </Text>
                        <div style={{ marginTop: "2%", }}>
                            <Button icon={ShareIcon} >View user guid</Button>
                        </div>
                    </LegacyCard>

                    <LegacyCard sectioned title="Rate the app">
                        <Text variant="headingXs" as="h6">
                            Your reviews keep our small team motivated to make the app even better.
                        </Text>
                        <div style={{ marginTop: "2%", }}>
                            <Button icon={StarFilledIcon} >Rate us on the shopify app store.</Button>
                        </div>
                    </LegacyCard>
                    <div style={{ marginTop: "2%" }}></div>
                </Grid.Cell>

                <Grid.Cell columnSpan={{ xs: 4, sm: 3, md: 3, lg: 4, xl: 4 }}>
                    <LegacyCard sectioned title="Checkout  our  other apps">
                    <div style={{ marginTop: "2%", }}>
                            <Text variant="headingXs" as="h6">
                                Meetanshi AI Content Generator
                            </Text>
                            <div style={{ marginTop: "2%" }}></div>
                            <Text variant="bodyMd" as="p">
                                Effortlessly generate content for products, collections, and blog posts. Improve content & SEO.
                            </Text>
                            <div style={{ marginTop: "3%", marginBottom: '3%' }}>
                                <Button icon={ShareIcon} onClick={handleButtonAiContente} >Start your free trial</Button>
                            </div>
                            <Divider borderColor="border" />
                        </div>

                        <div style={{ marginTop: "6%", }}>
                            <Text variant="headingXs" as="h6">
                                PDF Product Catalog
                            </Text>
                            <div style={{ marginTop: "2%" }}></div>
                            <Text variant="bodyMd" as="p">
                            Quickly create product PDF catalogs. Allow users to print product catalogs on collection pages.
                            </Text>
                            <div style={{ marginTop: "3%", marginBottom: '3%' }}>
                                <Button icon={ShareIcon} onClick={handleButtonPdf}>Get the app</Button>
                            </div>
                            <Divider borderColor="border" />
                        </div>


                        <div style={{ marginTop: "6%", }}>
                            <Text variant="headingXs" as="h6">
                            Meet Collections Import Export
                            </Text>
                            <div style={{ marginTop: "2%" }}></div>
                            <Text variant="bodyMd" as="p">
                            Easily export & import collections between stores. Bulk migrate automated & manual collections.                            </Text>
                            <div style={{ marginTop: "3%", marginBottom: '3%' }}>
                                <Button icon={ShareIcon} onClick={handleButtonCollections} >Start your free trial</Button>
                            </div>
                            <Divider borderColor="border" />
                        </div>

                       
                     
                    </LegacyCard>
                    <div style={{ marginTop: "3%" }}></div>
                </Grid.Cell>
            </Grid>
        </Page>
    )
}

export default Support
