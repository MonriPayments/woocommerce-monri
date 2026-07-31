import { decodeEntities } from '@wordpress/html-entities';
import { Fragment } from "react";
import { __ } from '@wordpress/i18n';
import { getDefaultPaymentMethod } from "../default-payment-method";

export const Card = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Components', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        content: <Card />,
        edit: <Card />,
    };
};
