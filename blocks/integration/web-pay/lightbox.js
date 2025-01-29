import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { Fragment } from "react";
import { Installments } from "../installments";
import { getDefaultPaymentMethod } from "../default-payment-method";
import { addAction } from '@wordpress/hooks';


export const WebPayLightbox = () => {
    const settings = useMonriData();

    const showInstallments = settings.installments;
    return <Fragment>
        {decodeEntities(settings.description || '')}
    </Fragment>;
};
console.log('yoyoy');
addAction(
    'wc.blocks.checkout.placeOrder',
    'woocommerce-monri/checkout-success',
     (response) => {
        console.log('Order completed2:', response);
        // Perform actions when order is successfully placed
    }
);

export const getPaymentMethod = (payment) => {
    console.log('hi');

    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayLightbox />,
        edit: <WebPayLightbox />,
    };
};
