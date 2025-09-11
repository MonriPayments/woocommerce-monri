import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";
import {Fragment} from "react";
import {Installments} from "../installments";

/**
 * React component that renders the Keks payment fields
 */
export const KeksPay = () => {
    const settings = useMonriData();
    return <Fragment>
        {decodeEntities('Pay with Monri Keks')}
    </Fragment>;
};


export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        name: 'monri-components-keks',
        label: 'Monri Keks',
        content: <KeksPay />,
        edit: <KeksPay />,
        supports: { features: ['products', 'refunds'] },
    };
};


