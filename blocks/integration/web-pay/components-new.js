import { Fragment } from "react";
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { useMonriData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";

/**
 * New WebPay Components - order is created before payment.
 * No card form on checkout; payment is completed on the receipt/pay page.
 */
export const WebPayComponentsNew = () => {
    const settings = useMonriData();

    return <Fragment>
        {decodeEntities(settings.description || '')}
        <br />
        <small>{__('You will be redirected to complete your payment after placing the order.', 'monri')}</small>
    </Fragment>;
};

export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayComponentsNew />,
        edit: <WebPayComponentsNew />,
    };
};

