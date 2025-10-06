import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsApplePayData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';




export const ApplePay = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Apple Pay', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsApplePayData();

    const label = decodeEntities( settings.title ) || __( 'Monri Apple Pay', 'monri' );

    return {
        name: 'monri_components_apple_pay',
        label,
        ariaLabel: label,
        content: <ApplePay />,
        edit: <ApplePay />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


