import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsGooglePayData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const GooglePay = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Google Pay', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsGooglePayData();

    const label = decodeEntities( settings.title ) || __( 'Monri Google Pay', 'monri' );

    return {
        name: 'monri_components_google_pay',
        label,
        ariaLabel: label,
        content: <GooglePay />,
        edit: <GooglePay />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


