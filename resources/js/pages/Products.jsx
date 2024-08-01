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
    Icon
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
    const [selected, setSelected] = useState(0);
    const [country, setCountry] = useState([])
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [allCountries, setAllCountries] = useState([]);
    const [inputValue, setInputValue] = useState('');
    const [Product, setProduct] = useState([])
    const [value, setValue] = useState('');
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [pageInfo, setPageInfo] = useState({
        startCursor: null,
        endCursor: null,
        hasNextPage: false,
        hasPreviousPage: false
    });

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
        errorMessage: "This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.",
        showMethod: "no",
        sortOrder: "1",
        minOrder: "1",
        maxOrder: "100",
        productdata: []
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

    const handleProductDataChange = (index, key, value) => {
        const updatedProductData = [...formData.productdata];
        if (!updatedProductData[index]) {
            updatedProductData[index] = {};
        }
        updatedProductData[index][key] = value;
        setFormData((prevState) => ({
            ...prevState,
            productdata: updatedProductData,
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

    const fetchProducts = async () => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            console.log(token)
            const response = await axios.post(`${apiCommonURL}/api/products`, Product, {
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
    }

    useEffect(() => {
        getCountry()
        fetchProducts()
    }, [])


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

    const { selectedResources, allResourcesSelected, handleSelectionChange } =
        useIndexResourceState(filteredProducts);

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
    const rowMarkup = filteredProducts.map(({ id, title, image, price }, index) => (
        <IndexTable.Row
            id={id}
            key={id}
            selected={selectedResources.includes(id)}
            position={index}
          
        >
            <IndexTable.Cell>
                <Thumbnail
                    source={image}
                    size="large"
                    alt="Black choker necklace"
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
                <TextField
                    
                    value={formData.productdata[index]?.value || ''}
                    onChange={(value) => handleProductDataChange(index, 'value', value)}
                    autoComplete="off"
                />
            </IndexTable.Cell>
        </IndexTable.Row>
    ));

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
        selectedOptions.length > 0 ? (
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
            // error={errors.selectedCountries}
            autoComplete="off"
        />
    );
    const saevConfig = async () => {
        try {
            const app = createApp({
                apiKey: SHOPIFY_API_KEY,
                host: new URLSearchParams(location.search).get("host"),
            });
            const token = await getSessionToken(app);
            const selectedCountries = selectedOptions.map(option => {
                const selectedCountry = country.find(country => country.value === option);
                return {
                    name: selectedCountry ? selectedCountry.label : '',
                    code: option
                };
            });
            const dataToSubmit = {
                ...formData,
                country: selectedCountries,
            };
            console.log(dataToSubmit)
            const response = await axios.post(`${apiCommonURL}/api/mixMergeRate`, dataToSubmit, {
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
                                        <Button variant="primary" onClick={saevConfig}>Save</Button>
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

                                        <div style={{ marginTop: "0.3%" }} >
                                            <FormLayout.Group>
                                                <Select
                                                    label="Ship to Applicable Countries"
                                                    options={applicableCountries}
                                                    onChange={handleSelectChange('applicableCountries')}
                                                    value={formData.applicableCountries}
                                                />
                                                <div style={{ pointerEvents: formData.applicableCountries === 'all' ? 'none' : 'auto' }}>
                                                    <Autocomplete
                                                        allowMultiple
                                                        options={country}
                                                        selected={selectedOptions}
                                                        textField={textField}
                                                        onSelect={setSelectedOptions}
                                                        listTitle="Suggested Countries"
                                                    />
                                                </div>
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
                                    <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                        <Button variant="primary" onClick={saevConfig}>Save </Button>
                                    </div>
                                    <div style={{marginTop:"2.5%"}}>

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
                                <div style={{marginTop:"2%"}}>
                                    <IndexTable
                                        resourceName={resourceName}
                                        itemCount={filteredProducts.length}
                                        selectedItemsCount={
                                            allResourcesSelected ? 'All' : selectedResources.length
                                        }
                                        onSelectionChange={handleSelectionChange}
                                        headings={[
                                            { title: 'Image' },
                                            { title: 'Title' },
                                            { title: 'Price' },
                                            { title: 'Rate Price' },
                                        ]}
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
        </Page >
    )
}

export default Products
