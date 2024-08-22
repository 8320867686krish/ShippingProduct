import React from 'react';
import { Route } from "react-router";
import { Routes } from 'react-router-dom';
import Products from '../pages/Products';
import Plans from '../pages/Plans';


export default function Routing(props) {
    return (
        <Routes>
            <Route  index element={<Products {...props} />} />
            <Route  path="/Plans" element={<Plans {...props} />} />
        </Routes>
    );
}
