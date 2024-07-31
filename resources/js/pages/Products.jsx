import React, { useState, useCallback } from 'react'
import axios from 'axios';
import {
    LegacyCard,
    LegacyTabs,
    Page,
    TextField,
    FormLayout,
    Select,
    Text,
    Button
} from '@shopify/polaris';
import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";
const SHOPIFY_API_KEY = import.meta.env.VITE_SHOPIFY_API_KEY;
const apiCommonURL = import.meta.env.VITE_COMMON_API_URL;

function Products() {
    const [selected, setSelected] = useState(0);
    const [formData, setFormData] = useState({
        enable: 'yes',
        title: 'Flat Rate Canada',
        shippingRate: 'item',
        rate_calculation: 'max',
        method: "Test",
        productShippinCost: "no",
        ratePerItem: "10",
        handlingFee: '0',
        applicableCountries: "all",
        specificCountries: "",
        errorMessage: "This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.",
        showMethod: "no",
        sortOrder: "1",
        minOrder: "1",
        maxOrder: "100"
    })
    const handleTabChange = useCallback((selectedTabIndex) => setSelected(selectedTabIndex), []);
    const tabs = [
        {
            id: 'all-customers-1',
            content: 'Configuration ',
            accessibilityLabel: 'All customers',
            panelID: 'all-customers-content-1',
        },
        {
            id: 'accepts-marketing-1',
            content: 'Products',
            panelID: 'accepts-marketing-content-1',
        },
    ];
    const handleChange = (field) => (value) => {
        setFormData((prevState) => ({
            ...prevState,
            [field]: value,
        }));
    };
    const handleSelectChange = (field) => (value) => {
        setFormData({
            ...formData,
            [field]: value,
        });
    };
    const Enabled = [
        { label: 'Yes', value: 'yes' },
        { label: 'No', value: 'no' },
    ]
    const shippingRate = [
        { label: 'per Item(s)', value: 'item' },
        { label: 'per Order', value: 'orderno' },
    ]
    const applicableCountries = [
        { label: 'All Allowed Countries', value: 'all' },
        { label: 'Specific Countries', value: 'specific' },
    ]
    const Ratecalculation = [
        { label: 'Sum of Rate', value: 'sum' },
        { label: 'Maximum value', value: 'max' },
        { label: 'Minimum value', value: 'min' },
    ]

    const saevConfig = async () => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            console.log(token)
            const response = await axios.post(`${apiCommonURL}/api/mixMergeRate`, formData, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

        } catch (error) {
            console.error('Error occurs', error);

        }
    }

    return (
        <Page title="Configuration And Products">
            <div style={{ marginBottom: "3%" }}>
                <LegacyCard>
                    <LegacyTabs tabs={tabs} selected={selected} onSelect={handleTabChange}>
                        <LegacyCard.Section>
                            {selected === 0 && (
                                <div>
                                    <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                        <Button variant="primary" onClick={saevConfig}>Save Config</Button>
                                    </div>
                                    <FormLayout>
                                        <FormLayout.Group>
                                            <Select
                                                label="Enabled"
                                                options={Enabled}
                                                onChange={handleSelectChange('enable')}
                                                value={formData.enable}
                                            />
                                            <TextField
                                                type="text"
                                                label="Title"
                                                value={formData.title}
                                                onChange={handleChange('title')}
                                            />
                                        </FormLayout.Group>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <Select
                                                    label="Shipping Rate"
                                                    options={shippingRate}
                                                    onChange={handleSelectChange('shippingRate')}
                                                    value={formData.shippingRate}
                                                />
                                                <Select
                                                    label="Shipping Rate"
                                                    options={Ratecalculation}
                                                    onChange={handleSelectChange('rate_calculation')}
                                                    value={formData.rate_calculation}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Method Name	"
                                                    value={formData.method}
                                                    onChange={handleChange('method')}
                                                />
                                                <Select
                                                    label="Default Product Shipping Cost"
                                                    options={Enabled}
                                                    onChange={handleSelectChange('productShippinCost')}
                                                    value={formData.productShippinCost}
                                                    helpText='If set to "Yes", the default rate per item will be used for all products.'
                                                />

                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Default Rate Per Item"
                                                    value={formData.ratePerItem}
                                                    onChange={handleChange('ratePerItem')}
                                                />
                                                <TextField
                                                    type="text"
                                                    label="Handling Fee"
                                                    value={formData.handlingFee}
                                                    onChange={handleChange('handlingFee')}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <Select
                                                    label="Ship to Applicable Countries"
                                                    options={applicableCountries}
                                                    onChange={handleSelectChange('applicableCountries')}
                                                    value={formData.applicableCountries}
                                                />
                                                <Select
                                                    label="Ship to Specific Countries"
                                                    options={Ratecalculation}
                                                    onChange={handleSelectChange('specificCountries')}
                                                    value={formData.specificCountries}
                                                    disabled={formData.applicableCountries === 'all'}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <TextField
                                                type="text"
                                                label="Displayed Error Message"
                                                value={formData.errorMessage}
                                                multiline={3}
                                                onChange={handleChange('errorMessage')}
                                            />
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Sort Order"
                                                    value={formData.sortOrder}
                                                    onChange={handleChange('sortOrder')}
                                                />
                                                <Select
                                                    label="Show Method only for Admin"
                                                    options={Enabled}
                                                    onChange={handleSelectChange('showMethod')}
                                                    value={formData.showMethod}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Minimum Order Amount"
                                                    value={formData.minOrder}
                                                    onChange={handleChange('minOrder')}
                                                />
                                                <TextField
                                                    type="text"
                                                    label="Maximum Order Amount"
                                                    value={formData.maxOrder}
                                                    onChange={handleChange('maxOrder')}
                                                />
                                            </FormLayout.Group>
                                        </div>
                                    </FormLayout>
                                </div>
                            )}
                            {selected === 1 && (
                                <div>
                                    hwu
                                </div>
                            )}
                        </LegacyCard.Section>
                    </LegacyTabs>
                </LegacyCard>
            </div>
        </Page >
    )
}

export default Products
