import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsFlikPayData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const FlikPay = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Flik Pay', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsFlikPayData();

    const label = decodeEntities( settings.title ) || __( 'Monri Flik Pay', 'monri' );

    return {
        name: 'monri_components_flik_pay',
        label,
        ariaLabel: label,
        content: <FlikPay />,
        edit: <FlikPay />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


