import React from 'react';
import { Route } from "react-router";
import { Routes } from 'react-router-dom';
import Products from '../pages/Products';
import Support from '../pages/Support';
import Main from '../pages/Main';

export default function Routing(props) {
    return (
        <Routes>
            <Route  index element={<Products {...props} />} />
            <Route exact path="/Main" element={<Main {...props} />} />
            <Route  path="/helpCenter" element={<Support {...props} />} />
        </Routes>
    );
}
