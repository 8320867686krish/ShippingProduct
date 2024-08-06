import React, { useState, useCallback, useEffect } from 'react'
import axios from 'axios';
import {
    LegacyCard,
    LegacyTabs,
    Page,
    TextField,
    FormLayout,
    Select,
    Text,
    Button,
    Autocomplete,
    LegacyStack,
    Tag,
    useIndexResourceState,
    IndexTable,
    Thumbnail,
    Icon,
    Toast,
    Checkbox,
    SkeletonTabs
} from '@shopify/polaris';
import {
    SearchIcon,
    PlusIcon
} from '@shopify/polaris-icons';
import createApp from '@shopify/app-bridge';
import { getSessionToken } from "@shopify/app-bridge-utils";
const SHOPIFY_API_KEY = import.meta.env.VITE_SHOPIFY_API_KEY;
const apiCommonURL = import.meta.env.VITE_COMMON_API_URL;

function Products() {
    const [selected, setSelected] = useState(1);
    const [country, setCountry] = useState([])
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [allCountries, setAllCountries] = useState([]);
    const [inputValue, setInputValue] = useState('');
    const [Product, setProduct] = useState([])
    const [value, setValue] = useState('');
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [toastContent, setToastContent] = useState("");
    const [showToast, setShowToast] = useState(false);
    const toastDuration = 3000
    const [startIndex, setStartIndex] = useState(0);

    const [loading, setLoading] = useState(true)
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
        title: 'Flat Rate Canada',
        shipping_rate: 1,
        shipping_rate_calculation: 2,
        method_name: "Test",
        product_shipping_cost: 0,
        rate_per_item: 10,
        handling_fee: 0,
        applicable_countries: 0,
        displayed_error_message: "This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.",
        show_method_for_admin: 0,
        sort_order: 1,
        min_order_amount: 1,
        max_order_amount: 100,
        method_if_not_applicable: 0,
        productdata: [],
        countries: ''
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

    const handleSelectChange = (field, value) => {
        setFormData(prevState => ({
            ...prevState,
            [field]: value
        }));
    };

    const saevConfig = async () => {

        try {
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
            console.log(dataToSubmit)
            const response = await axios.post(`${apiCommonURL}/api/settings/save`, dataToSubmit, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            setToastContent('Rate saved successfully');
            setShowToast(true);
            window.location.reload();

        } catch (error) {
            console.error('Error occurs', error);
            setToastContent('Error occurred while saving data');
            setShowToast(true);

        }
    }

    const getCountry = async () => {
        try {
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
            const payload = direction === 'next' ? { endCursor: cursor } : { startCursor: cursor };

            const response = await axios.post(`${apiCommonURL}/api/products`, payload, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            const productData = response.data;
            setProduct(productData.products);
            setFilteredProducts(productData.products);
            setPageInfo({
                startCursor: productData.startCursor,
                endCursor: productData.endCursor,
                hasNextPage: productData.hasNextPage,
                hasPreviousPage: productData.hasPreviousPage,
            });

        } catch (error) {
            console.error('Error occurs', error);
        }
    };

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

            console.log(response.data, 'Data received from API');
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
                displayed_error_message: apiData.displayed_error_message,
                show_method_for_admin: apiData.show_method_for_admin,
                sort_order: apiData.sort_order,
                min_order_amount: apiData.min_order_amount,
                max_order_amount: apiData.max_order_amount,
                method_if_not_applicable: apiData.method_if_not_applicable,
                productdata: apiData.productdata,
            });
            setSelectedOptions(Array.isArray(apiData.countries) ? apiData.countries : []);

        } catch (error) {
            console.error('Error occurs', error);
        }
    };
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
            label="Select Countries"
            value={inputValue}
            placeholder="Search countries"
            verticalContent={verticalContentMarkup}

            autoComplete="off"
        />
    );

    const handleserchChange = useCallback(
        (newValue) => {
            setValue(newValue);
            if (newValue === '') {
                setFilteredProducts(Product);
            } else {
                const lowerCaseValue = newValue.toLowerCase();
                setFilteredProducts(Product.filter(product =>
                    product.title.toLowerCase().includes(lowerCaseValue)
                ));
            }
        },
        [Product]
    );

    const handleClearButtonClick = useCallback(() => {
        setValue('');
        setFilteredProducts(Product);
    }, [Product]);

    const resourceName = {
        singular: 'Products',
        plural: 'Products',
    };

    const handleProductDataChange = (key, value, productId) => {
        const product2 = filteredProducts.find(p => p.id === productId);
    
        const updatedProductData = [...formData.productdata];
        const productIndex = updatedProductData.findIndex(p => p.product_id === productId);
    
        if (productIndex === -1) {
            updatedProductData.push({
                product_id: product2.id,
                title: product2.title,
                price: product2.price,
                [key]: value,
            });
        } else {
            updatedProductData[productIndex][key] = value;
        }
    
        console.log(updatedProductData);
        setFormData((prevState) => ({
            ...prevState,
            productdata: updatedProductData,
        }));
    };
    console.log(formData.productdata)

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


    const rowMarkup = filteredProducts.map(({ id, title, image, price }, index) => {
      

        return (
            <IndexTable.Row
                id={id}
                key={id}
                position={id}
            >
                 {/* <IndexTable.Cell>
                <Checkbox
                    checked={formData.productdata.find(p => p.product_id === id)?.checked || false}
                    onChange={(checked) => handleProductDataChange('checked', checked, id)}
                />
            </IndexTable.Cell> */}
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
                            value={formData.productdata.find(p => p.product_id == id)?.value || ''}
                            onChange={(value) => handleProductDataChange('value', value, id)}
                            autoComplete="off"
                        />
                    </div>
                </IndexTable.Cell>
            </IndexTable.Row>
        );
    });

    if (loading) {
        <Page title="Configuration And Products">
            <div style={{ marginTop: "3%" }}>
                <LegacyCard>
                    <SkeletonTabs />
                </LegacyCard>
            </div>
        </Page>
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
                                        <Button variant="primary" onClick={saevConfig}>Save</Button>
                                    </div>
                                    <FormLayout>
                                        <FormLayout.Group>
                                            <Select
                                                label="enabledd"
                                                options={enabledd}
                                                onChange={(value) => handleSelectChange('enabled', parseInt(value))}
                                                value={formData.enabled}
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
                                                    options={shipping_rate}
                                                    onChange={(value) => handleSelectChange('shipping_rate', parseInt(value))}
                                                    value={formData.shipping_rate}
                                                />
                                                <Select
                                                    label="Shipping Rate"
                                                    options={Ratecalculation}
                                                    onChange={(value) => handleSelectChange('shipping_rate_calculation', parseInt(value))}
                                                    value={formData.shipping_rate_calculation}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Method Name	"
                                                    value={formData.method_name}
                                                    onChange={handleChange('method_name')}
                                                />
                                                <Select
                                                    label="Default Product Shipping Cost"
                                                    options={enabledd}
                                                    onChange={(value) => handleSelectChange('product_shipping_cost', parseInt(value))}
                                                    value={formData.product_shipping_cost}
                                                    helpText='If set to "Yes", the default rate per item will be used for all products.'
                                                />

                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="text"
                                                    label="Default Rate Per Item"
                                                    value={formData.rate_per_item}
                                                    onChange={handleChange('rate_per_item')}
                                                />
                                                <TextField
                                                    type="text"
                                                    label="Handling Fee"
                                                    value={formData.handling_fee}
                                                    onChange={handleChange('handling_fee')}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }} >
                                            <FormLayout.Group>
                                                <Select
                                                    label="Ship to Applicable Countries"
                                                    options={applicable_countries}
                                                    onChange={(value) => handleSelectChange('applicable_countries', parseInt(value))}
                                                    value={formData.applicable_countries}
                                                />
                                                <div style={{ pointerEvents: formData.applicable_countries === 0 ? 'none' : 'auto' }}>
                                                    <Autocomplete
                                                        allowMultiple
                                                        options={country}
                                                        selected={selectedOptions}
                                                        textField={textField}
                                                        onSelect={(selected) => {
                                                            setSelectedOptions(selected);
                                                        }}
                                                        listTitle="Suggested Countries"
                                                    />
                                                </div>
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                {formData.applicable_countries === 1 && (
                                                    <Select
                                                        label="Show Method if Not Applicable"
                                                        options={enabledd}
                                                        onChange={(value) => handleSelectChange('method_if_not_applicable', parseInt(value))}
                                                        value={formData.method_if_not_applicable}
                                                    />
                                                )}

                                                <TextField
                                                    type="text"
                                                    label="Displayed Error Message"
                                                    value={formData.displayed_error_message}
                                                    multiline={3}
                                                    onChange={handleChange('displayed_error_message')}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="number"
                                                    label="Sort Order"
                                                    value={formData.sort_order}
                                                    onChange={handleChange('sort_order')}
                                                />
                                                <Select
                                                    label="Show Method only for Admin"
                                                    options={enabledd}
                                                    onChange={(value) => handleSelectChange('show_method_for_admin', parseInt(value))}
                                                    value={formData.show_method_for_admin}
                                                />
                                            </FormLayout.Group>
                                        </div>

                                        <div style={{ marginTop: "0.3%" }}>
                                            <FormLayout.Group>
                                                <TextField
                                                    type="number"
                                                    label="Minimum Order Amount"
                                                    value={formData.min_order_amount}
                                                    onChange={handleChange('min_order_amount')}
                                                />
                                                <TextField
                                                    type="number"
                                                    label="Maximum Order Amount"
                                                    value={formData.max_order_amount}
                                                    onChange={handleChange('max_order_amount')}
                                                />
                                            </FormLayout.Group>
                                        </div>
                                    </FormLayout>
                                </div>
                            )}

                            {selected === 1 && (
                                <div>
                                    <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                        <Button variant="primary" onClick={saevConfig}>Save </Button>
                                    </div>
                                    <div style={{ marginTop: "2.5%" }}>

                                        <TextField
                                            placeholder='search'
                                            onChange={handleserchChange}
                                            value={value}
                                            type="text"
                                            prefix={<Icon source={SearchIcon} color="inkLighter" />}
                                            autoComplete="off"
                                            clearButton
                                            onClearButtonClick={handleClearButtonClick}
                                        />
                                    </div>
                                    <div style={{ marginTop: "2%" }}>
                                        <IndexTable
                                            resourceName={resourceName}
                                            itemCount={filteredProducts.length}

                                            headings={[
                                                // { title: `Selected (${selectedCount})` },
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
                        </LegacyCard.Section>
                    </LegacyTabs>
                </LegacyCard>
            </div>
            {showToast && (
                <Toast content={toastContent} duration={toastDuration} onDismiss={() => setShowToast(false)} />
            )}
        </Page >
    )
}

export default Products
