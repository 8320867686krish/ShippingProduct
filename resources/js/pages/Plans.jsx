import React from 'react';

function Plans() {
    const handlePlanSelect = (plan) => {
        const name = 'shipping-product';
        window.top.location.href = `https://admin.shopify.com/charges/${name}/pricing_plans?plan=${plan}`;
    };

    return (
        <div>
            <button onClick={handlePlanSelect}>click</button>
        </div>
    );
}

export default Plans;
