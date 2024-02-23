import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { Fragment } from "react";
import { Installments } from "../installments";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const WebPayForm = () => {
    const settings = useMonriData();

    return <Fragment>
        {decodeEntities(settings.description || '')}
        <Installments />
    </Fragment>;
};

export const getPaymentMethod = (payment) => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayForm />,
        edit: <WebPayForm />,
    };
};