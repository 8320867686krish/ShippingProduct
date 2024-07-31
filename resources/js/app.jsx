import React, { useEffect, useMemo, useState } from 'react';

import Index from './Index';
import { createRoot } from 'react-dom/client';

const container = document.getElementById('main');
const props = Object.assign({}, container.dataset)
const root = createRoot(container); // createRoot(container!) if you use TypeScript
window.shopId = props.shopid;

root.render(<Index tab="home"  {...props} />);
