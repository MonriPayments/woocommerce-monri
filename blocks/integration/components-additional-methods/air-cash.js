import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsAirCashData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const AirCash = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Air Cash', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsAirCashData();

    const label = decodeEntities( settings.title ) || __( 'Monri Air Cash', 'monri' );

    return {
        name: 'monri_components_air_cash',
        label,
        ariaLabel: label,
        content: <AirCash />,
        edit: <AirCash />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


