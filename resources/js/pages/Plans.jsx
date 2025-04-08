import { Redirect } from '@shopify/app-bridge/actions';
import { useAppBridge } from '@shopify/app-bridge-react';
import { Button } from '@shopify/polaris';
import React, { use } from 'react';

function PricingPlanRedirect() {
  const app = useAppBridge();

  const handleRedirect = () => {
    const redirect = Redirect.create(app);
    // Log the Redirect.Action.ADMIN_PATH to the console
    console.log('Redirect.Action.ADMIN_PATH:', Redirect.Action.ADMIN_PATH);

    redirect.dispatch(
      Redirect.Action.ADMIN_PATH,
      '/charges/meetanshi-shipping-per-product/pricing_plans'
    );
    //   redirect.dispatch(
    //     Redirect.Action.ADMIN_PATH,
    //     '/apps/khushi_test/store/khushi-sonani/charges/khushi_test/pricing_plans'
    //   );
  };

  useEffect(() => {
    handleRedirect();
  }
  , []);
  return <></>;
}

export default PricingPlanRedirect;
