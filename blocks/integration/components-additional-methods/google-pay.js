import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsGooglePayData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";
import {Fragment} from "react";



export const GooglePay = () => {
    return <Fragment>
        {decodeEntities('Pay with Monri Google Pay')}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsGooglePayData();
    if (!settings?.google_pay_enabled) {
        return null;
    }

    return {
        ...getDefaultPaymentMethod(),
        name: 'monri_components_google_pay',
        label: 'Monri Google Pay',
        content: <GooglePay />,
        edit: <GooglePay />,
        supports: { features: ['products'] },
    };
};


