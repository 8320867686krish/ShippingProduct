import React from 'react';
import { Route } from "react-router";
import { Routes } from 'react-router-dom';
import Products from '../pages/Products';
import Pricing_plans from '../pages/Pricing_plans';


export default function Routing(props) {
    return (
        <Routes>
            <Route exact path="/" element={<Products {...props} />} />


        </Routes>
    );
}
