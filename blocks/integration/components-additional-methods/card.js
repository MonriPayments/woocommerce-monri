import { decodeEntities } from '@wordpress/html-entities';
import { Fragment } from "react";
import { useMonriData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const Card = () => {
    const settings = useMonriData();

    return <Fragment>
        {decodeEntities(settings.description || '')}
    </Fragment>;
};

export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        content: <Card />,
        edit: <Card />,
    };
};
