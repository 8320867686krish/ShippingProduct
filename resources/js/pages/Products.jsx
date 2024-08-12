import React, { useState, useCallback, useEffect } from 'react'
import axios from 'axios';
import debounce from 'lodash.debounce';
import {
    LegacyCard,
    LegacyTabs,
    Page,
    TextField,
    Select,
    Text,
    Button,
    Autocomplete,
    LegacyStack,
    Tag,
    IndexTable,
    Thumbnail,
    Icon,
    Toast,
    Checkbox,
    Spinner,
    Card
} from '@shopify/polaris';
import { SearchIcon } from '@shopify/polaris-icons';
import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";
const SHOPIFY_API_KEY = import.meta.env.VITE_SHOPIFY_API_KEY;
const apiCommonURL = import.meta.env.VITE_COMMON_API_URL;

function Products() {
    const [selected, setSelected] = useState(0);
    const [country, setCountry] = useState([])
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [allCountries, setAllCountries] = useState([]);
    const [inputValue, setInputValue] = useState('');
    const [Product, setProduct] = useState([])
    const [formSave, setFormSave] = useState(false);
    const [textFieldValue, setTextFieldValue] = useState("");
    const [toastContent, setToastContent] = useState("");
    const [showToast, setShowToast] = useState(false);
    const toastDuration = 1000
    const [errors, setErrors] = useState({});
    const [errorToast, setErroToast] = useState(false)
    const [loading, setLoading] = useState(false)
    const [pageInfo, setPageInfo] = useState({
        startCursor: null,
        endCursor: null,
        hasNextPage: false,
        hasPreviousPage: false
    });
    const enabledd = [
        { label: 'Yes', value: 1 },
        { label: 'No', value: 0 },
    ]
    const shipping_rate = [
        { label: 'per Item(s)', value: 1 },
        { label: 'per Order', value: 2 },
    ]
    const applicable_countries = [
        { label: 'All Allowed Countries', value: 0 },
        { label: 'Specific Countries', value: 1 },
    ]
    const Ratecalculation = [
        { label: 'Sum of Rate', value: 1 },
        { label: 'Maximum value', value: 2 },
        { label: 'Minimum value', value: 3 },
    ]
    const [formData, setFormData] = useState({
        id: 0,
        enabled: 1,
        title: 'Flat Rate',
        shipping_rate: 1,
        shipping_rate_calculation: 2,
        method_name: "3 to 4 business day",
        product_shipping_cost: 0,
        rate_per_item: 10,
        handling_fee: 0,
        applicable_countries: 0,
        // displayed_error_message: "This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.",
        // show_method_for_admin: 0,
        // sort_order: 1,
        min_order_amount: 1,
        max_order_amount: 100,
        // method_if_not_applicable: 0,
        productdata: [],
        countries: ''
    })

    const handleTabChange = useCallback((selectedTabIndex) => {
        setLoading(true);
        setSelected(selectedTabIndex);
        setTimeout(() => {
            return setLoading(false);
        }, 500);
    }, []);
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
        const numericValue = Number(value);
    
        if (numericValue >= 0 || value === '') {
            setFormData((prevState) => ({
                ...prevState,
                [field]: value,
            }));
        }
    };
    
    const handleSelectChange = (field, value) => {
        if (field === 'applicable_countries' && value === 0) {
            setSelectedOptions([]);
        }
        if (field === 'applicable_countries' && value === 0) {
            setTextFieldError('');
        }
        setFormData(prevState => ({
            ...prevState,
            [field]: value
        }));
    };

    const getCountry = async () => {
        try {
            setLoading(true)
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            const response = await axios.get(`${apiCommonURL}/api/country`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const countryData = response.data.countries;
            const stateList = countryData.map(state => ({
                label: state.name,
                value: state.code
            }));
            setCountry(stateList);
            setAllCountries(stateList);
            setLoading(false)
        } catch (error) {
            console.error("Error fetching country:", error);
        }
    }

    const fetchProducts = async (cursor, direction) => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            const payload = {
                ...(direction === 'next' ? { endCursor: cursor } : { startCursor: cursor }),
                ...(textFieldValue ? { query: textFieldValue } : {}),
            };

            const response = await axios.post(`${apiCommonURL}/api/products`, payload, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            const productData = response.data;
            setProduct(productData.products);
            setPageInfo({
                startCursor: productData.startCursor,
                endCursor: productData.endCursor,
                hasNextPage: productData.hasNextPage,
                hasPreviousPage: productData.hasPreviousPage,
            });
        } catch (error) {
            console.error('Error occurs:', error.response ? error.response.data : error.message);
        }
    };

    const handleTextFieldChange = useCallback((value) => {
        setTextFieldValue(value);
        fetchProducts();
        if (textFieldValue == '') {
            fetchProducts();
        }
    }, [textFieldValue]);

    // const handleTextFieldChange = useCallback(
    //     debounce((value) => {
    //         setTextFieldValue(value);
    //         fetchProducts(); // Fetch products when the search text changes
    //     }, 300), // Debounce delay in milliseconds
    //     []
    // );

    const settingData = async () => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            const response = await axios.get(`${apiCommonURL}/api/setting`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const apiData = response.data.setting;

            setFormData({
                id: apiData.id,
                enabled: apiData.enabled,
                title: apiData.title,
                shipping_rate: apiData.shipping_rate,
                shipping_rate_calculation: apiData.shipping_rate_calculation,
                method_name: apiData.method_name,
                product_shipping_cost: apiData.product_shipping_cost,
                rate_per_item: apiData.rate_per_item,
                handling_fee: apiData.handling_fee,
                applicable_countries: apiData.applicable_countries,
                // displayed_error_message: apiData.displayed_error_message,
                // show_method_for_admin: apiData.show_method_for_admin,
                // sort_order: apiData.sort_order,
                min_order_amount: apiData.min_order_amount,
                max_order_amount: apiData.max_order_amount,
                // method_if_not_applicable: apiData.method_if_not_applicable,
                productdata: apiData.productdata,
            });
            setSelectedOptions(Array.isArray(apiData.countries) ? apiData.countries : []);
        } catch (error) {
            // console.error('Error occurs', error);
        }
    };
    const [textFieldError, setTextFieldError] = useState('');

    const saveConfig = async () => {
        try {
            const newErrors = {};
            const maxOrderAmount = Number(formData.max_order_amount);
            const minOrderAmount = Number(formData.min_order_amount);
            let hasProductError = false;
    
            if (!(maxOrderAmount === 0 && minOrderAmount === 0)) {
                if (maxOrderAmount <= minOrderAmount) {
                    newErrors.max_order_amount = 'Maximum Order Amount cannot be less than Minimum Order Amount';
                }
            }
    
            const updatedProductData = formData.productdata.map(product => {
                if (product.checked && !product.value) {
                    hasProductError = true;
                    return {
                        ...product,
                        error: 'Value is required',
                    };
                } else {
                    const { error, ...productWithoutError } = product;
                    return productWithoutError;
                }
            });
    
            if (formData.applicable_countries === 1 && selectedOptions.length === 0) {
                newErrors.selectedOptions = 'Please select at least one country';
                setTextFieldError('Please select at least one country');
            } else {
                setTextFieldError('');
            }
           
    
            if (Object.keys(newErrors).length > 0 || hasProductError) {
                setFormData(prevState => ({
                    ...prevState,
                    productdata: updatedProductData,
                }));
                setErrors(newErrors);
                setToastContent('Sorry. Couldn’t be saved. Please try again.');
                setErroToast(true);
                return;
            }
    
            setFormSave(true);
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            const countriesString = selectedOptions.join(',');
            const dataToSubmit = {
                ...formData,
                countries: countriesString,
            };
    
            const response = await axios.post(`${apiCommonURL}/api/settings/save`, dataToSubmit, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
    
            setErrors({});
            setShowToast(true);
            setToastContent('Data saved successfully');
            settingData();
            setFormSave(false);
        } catch (error) {
            console.error('Error occurs', error);
            setToastContent('Error occurred while saving data');
            setShowToast(true);
            setFormSave(false);
        }
    };
    
    console.log(formData.productdata)
    useEffect(() => {
        getCountry()
        fetchProducts()
        // if(formData.id){
        settingData()
        // }
    }, [])

    const updateText = useCallback(
        (value) => {
            setInputValue(value);
            if (value === '') {
                setCountry(allCountries);
                return;
            }
            const filterRegex = new RegExp(value, 'i');
            const resultOptions = allCountries.filter((option) =>
                option.label.match(filterRegex),
            );
            setCountry(resultOptions);
        },
        [allCountries],
    );

    const removeTag = useCallback(
        (tag) => () => {
            const newSelectedOptions = selectedOptions.filter(option => option !== tag);
            setSelectedOptions(newSelectedOptions);
        },
        [selectedOptions],
    );

    const verticalContentMarkup =
        selectedOptions && selectedOptions.length > 0 ? (
            <LegacyStack spacing="extraTight" alignment="center">
                {selectedOptions.map((option) => {
                    const tagLabel = country.find(opt => opt.value === option)?.label || option;
                    return (
                        <Tag key={option} onRemove={removeTag(option)}>
                            {tagLabel}
                        </Tag>
                    );
                })}
            </LegacyStack>
        ) : null;

        const textField = (
            <Autocomplete.TextField
                onChange={updateText}
                value={inputValue}
                placeholder="Search countries"
                verticalContent={verticalContentMarkup}
                autoComplete="off"
                error={textFieldError} // Show error message
            />
        );
        

    const resourceName = {
        singular: 'Products',
        plural: 'Products',
    };
    const [toastActive, setToastActive] = useState(false);
    const [toastMessage, setToastMessage] = useState('');
    const toggleToastActive = useCallback(() => setToastActive((active) => !active), []);

    const handleProductDataChange = (key, value, productId) => {
        const product2 = Product.find(p => p.id == productId);
        const updatedProductData = [...formData.productdata];
        const productIndex = updatedProductData.findIndex(p => p.product_id == productId);

        if (key === 'value' && value < 0) {
            setToastMessage('Value cannot be negative');
            setToastActive(true);
            return;
        }

        if (productIndex === -1) {
            const newProductData = {
                product_id: product2.id,
                title: product2.title,
                price: product2.price,
                [key]: value,
            };

            if (key === 'value' && value) {
                newProductData['checked'] = true;
                newProductData['error'] = '';
            }
            updatedProductData.push(newProductData);
        } else {
            updatedProductData[productIndex][key] = value;

            if (key === 'value') {
                if (value) {
                    updatedProductData[productIndex]['checked'] = true;
                    updatedProductData[productIndex]['error'] = '';
                } else {
                    updatedProductData[productIndex]['error'] = '';
                    updatedProductData[productIndex]['checked'] = false;
                }
            } else if (key === 'checked') {
                if (!value) {
                    updatedProductData[productIndex]['value'] = '';
                    updatedProductData[productIndex]['error'] = '';
                }
            }
        }

        setFormData((prevState) => ({
            ...prevState,
            productdata: updatedProductData,
        }));
    };

    const toastMarkup = toastActive ? (
        <Toast content={toastMessage} onDismiss={toggleToastActive} />
    ) : null;

    const handleNextPage = () => {
        if (pageInfo.hasNextPage) {
            fetchProducts(pageInfo.endCursor, 'next');
        }
    };
    const handlePreviousPage = () => {
        if (pageInfo.hasPreviousPage) {
            fetchProducts(pageInfo.startCursor, 'prev');
        }
    };
    const selectedCount = formData.productdata.filter(p => p.checked).length;

    const rowMarkup = Product.map(({ id, title, image, price }) => {
        const productData = formData.productdata.find(p => p.product_id == id);
        const isChecked = productData ? productData.checked : false;
        const productValue = productData ? productData.value : '';
        const productError = productData ? productData.error : '';

        return (
            <IndexTable.Row
                id={id}
                key={id}
                position={id}
            >
                <IndexTable.Cell>
                    <Checkbox
                        checked={isChecked}
                        onChange={(checked) => handleProductDataChange('checked', checked, id)}
                    />
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <Thumbnail
                        source={image}
                        size="small"
                        alt="Product Image"
                    />
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <Text fontWeight="bold" as="span">
                        {title}
                    </Text>
                </IndexTable.Cell>
                <IndexTable.Cell>
                    {price}
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <div style={{ width: "100px" }}>
                        <TextField
                            type='number'
                            value={productValue}
                            onChange={(value) => handleProductDataChange('value', value, id)}
                            error={productError}
                            autoComplete="off"
                        />
                    </div>
                </IndexTable.Cell>
            </IndexTable.Row>
        );
    });



    return (
        <Page title="Configuration And Products">
            <div style={{ marginBottom: "3%" }}>
                <Card>
                    <LegacyTabs tabs={tabs} selected={selected} onSelect={handleTabChange}>
                        <LegacyCard.Section>
                            {loading ? (
                                <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%', marginTop: "5%" }}>
                                    <Spinner accessibilityLabel="Loading" size="large" />
                                </div>
                            ) : (
                                <>
                                    {formSave && (
                                        <div style={{
                                            position: 'absolute',
                                            top: 0,
                                            left: 0,
                                            right: 0,
                                            bottom: 0,
                                            backgroundColor: 'rgba(255, 255, 255, 0.5)',
                                            display: 'flex',
                                            justifyContent: 'center',
                                            alignItems: 'center',
                                            zIndex: 9999
                                        }}>
                                            <Spinner accessibilityLabel="Loading" size="large" />
                                        </div>
                                    )}

                                    <div style={formSave ? { filter: 'blur(1px)', pointerEvents: 'none' } : {}}>
                                        {selected === 0 && (
                                            <div>
                                                <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: "1%" }}>
                                                    <Button variant="primary" onClick={saveConfig}>Save</Button>
                                                </div>

                                                <div style={{ display: 'flex', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Enabled
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={enabledd}
                                                            onChange={(value) => handleSelectChange('enabled', parseInt(value))}
                                                            value={formData.enabled}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Title
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="text"
                                                            value={formData.title}
                                                            onChange={handleChange('title')}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Description
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="text"
                                                            value={formData.method_name}
                                                            onChange={handleChange('method_name')}
                                                            helpText=''
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Shipping Rate
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={shipping_rate}
                                                            onChange={(value) => handleSelectChange('shipping_rate', parseInt(value))}
                                                            value={formData.shipping_rate}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Shipping Rate Calculation
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={Ratecalculation}
                                                            onChange={(value) => handleSelectChange('shipping_rate_calculation', parseInt(value))}
                                                            value={formData.shipping_rate_calculation}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%", }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Default Product Shipping Cost
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={enabledd}
                                                            onChange={(value) => handleSelectChange('product_shipping_cost', parseInt(value))}
                                                            value={formData.product_shipping_cost}
                                                        helpText='If set to "Yes", the default rate per item will be used for all products.'
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Default Rate Per Item
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="text"
                                                            value={formData.rate_per_item}
                                                            onChange={handleChange('rate_per_item')}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Handling Fee
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="text"
                                                            value={formData.handling_fee}
                                                            onChange={handleChange('handling_fee')}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Ship to Applicable Countries
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={applicable_countries}
                                                            onChange={(value) => handleSelectChange('applicable_countries', parseInt(value))}
                                                            value={formData.applicable_countries}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Ship to Specific Countries
                                                        </Text>
                                                    </div>
                                                    <div style={{ pointerEvents: formData.applicable_countries === 0 ? 'none' : 'auto', width: "70%" }}>
                                                        <Autocomplete
                                                            allowMultiple
                                                            options={country}
                                                            selected={selectedOptions}
                                                            textField={textField}
                                                            onSelect={(selected) => {
                                                                setSelectedOptions(selected);
                                                                setInputValue('');
                                                                setCountry(allCountries);
                                                                setTextFieldError('');
                                                            }}
                                                            listTitle="Suggested Countries"
                                                        />
                                                    </div>
                                                </div>
                                                {/* {formData.applicable_countries === 1 && (
                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Show Method if Not Applicable
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <Select
                                                            options={enabledd}
                                                            onChange={(value) => handleSelectChange('method_if_not_applicable', parseInt(value))}
                                                            value={formData.method_if_not_applicable}
                                                        />
                                                    </div>
                                                </div>
                                            )} */}

                                                {/* <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                    <Text variant="headingSm" as="h6">
                                                        Displayed Error Message
                                                    </Text>
                                                </div>
                                                <div style={{ flex: 1, width: "70%" }}>
                                                    <TextField
                                                        type="text"
                                                        value={formData.displayed_error_message}
                                                        multiline={3}
                                                        onChange={handleChange('displayed_error_message')}
                                                    />
                                                </div>
                                            </div> */}
                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Minimum Order Amount
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="number"
                                                            value={formData.min_order_amount}
                                                            onChange={handleChange('min_order_amount')}
                                                        />
                                                    </div>
                                                </div>

                                                <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                    <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                        <Text variant="headingSm" as="h6">
                                                            Maximum Order Amount
                                                        </Text>
                                                    </div>
                                                    <div style={{ flex: 1, width: "70%" }}>
                                                        <TextField
                                                            type="number"
                                                            value={formData.max_order_amount}
                                                            onChange={handleChange('max_order_amount')}
                                                            error={errors.max_order_amount}
                                                        />
                                                    </div>
                                                </div>
                                                {/* <div style={{ display: 'flex', alignItems: 'center', marginTop: "2%" }}>
                                                <div style={{ width: '30%', textAlign: 'left', paddingRight: '10px' }}>
                                                    <Text variant="headingSm" as="h6">
                                                        Sort Order
                                                    </Text>
                                                </div>
                                                <div style={{ flex: 1, width: "70%" }}>
                                                    <TextField
                                                        type="number"
                                                        value={formData.sort_order}
                                                        onChange={handleChange('sort_order')}
                                                    />
                                                </div>
                                            </div> */}

                                            </div>
                                        )}

                                        {selected === 1 && (
                                            <div>
                                                <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                                    <Button variant="primary" onClick={saveConfig}>Save </Button>
                                                </div>
                                                <div style={{ marginTop: "2.5%" }}>
                                                    <TextField
                                                        type="text"
                                                        value={textFieldValue}
                                                        placeholder="Search by Title..."
                                                        onChange={handleTextFieldChange}
                                                        prefix={<Icon source={SearchIcon} />}
                                                        autoComplete="off"
                                                    // clearButton
                                                    // onClearButtonClick={handleClearButtonClick}
                                                    />
                                                </div>
                                                <div style={{ marginTop: "2%" }}>
                                                    <IndexTable
                                                        resourceName={resourceName}
                                                        itemCount={Product.length}
                                                        headings={[
                                                            { title: `${selectedCount} Selected` },
                                                            { title: 'Image' },
                                                            { title: 'Title' },
                                                            { title: 'Price' },
                                                            { title: 'Rate Price' },
                                                        ]}
                                                        selectable={false}
                                                        pagination={{
                                                            hasNext: pageInfo.hasNextPage,
                                                            onNext: handleNextPage,
                                                            hasPrevious: pageInfo.hasPreviousPage,
                                                            onPrevious: handlePreviousPage,
                                                        }}
                                                    >
                                                        {rowMarkup}
                                                    </IndexTable>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                        </LegacyCard.Section>
                    </LegacyTabs>
                </Card>
            </div>
            {showToast && (
                <Toast content={toastContent} duration={toastDuration} onDismiss={() => setShowToast(false)} />
            )}
            {errorToast && (
                <Toast content={toastContent} error duration={toastDuration} onDismiss={() => setErroToast(false)} />
            )}
            {toastMarkup}
        </Page >
    )
}

export default Products
