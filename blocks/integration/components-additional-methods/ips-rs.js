import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsIpsRsData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const IpsRs = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Ips Rs', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsIpsRsData();

    const label = decodeEntities( settings.title ) || __( 'Monri Ips Rs', 'monri' );

    return {
        name: 'monri_components_ips_rs',
        label,
        ariaLabel: label,
        content: <IpsRs />,
        edit: <IpsRs />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


