import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsPayCekData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const PayCek = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri PayCek', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsPayCekData();

    const label = decodeEntities( settings.title ) || __( 'Monri PayCek', 'monri' );

    return {
        name: 'monri_components_pay_cek',
        label,
        ariaLabel: label,
        content: <PayCek />,
        edit: <PayCek />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


