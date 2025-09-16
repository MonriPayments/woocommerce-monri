import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsKeksData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";
import {Fragment} from "react";


/**
 * React component that renders the Keks payment fields
 */
export const KeksPay = () => {
    return <Fragment>
        {decodeEntities('Pay with Monri Keks')}
    </Fragment>;
};


export const getPaymentMethod = () => {

    const settings = useMonriComponentsKeksData();
    if (!settings?.keks_enabled) {
        return null;
    }

    return {
        ...getDefaultPaymentMethod(),
        name: 'monri_components_keks',
        label: 'Monri Keks',
        content: <KeksPay />,
        edit: <KeksPay />,
        supports: { features: ['products'] },
    };
};


