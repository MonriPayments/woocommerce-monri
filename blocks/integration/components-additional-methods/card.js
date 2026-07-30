import { decodeEntities } from '@wordpress/html-entities';
import {useMonriComponentsGooglePayData, useMonriData} from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const Card = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Components', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriData();

    const label = decodeEntities( settings.title ) || __( 'Monri Components', 'monri' );

    return {
        name: 'monri_components_card',
        label,
        ariaLabel: label,
        content: <Card />,
        edit: <Card />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


