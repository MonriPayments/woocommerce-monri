import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsIpsOtpData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';



export const IpsOtp = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Ips Otp', 'monri'))}
    </Fragment>;
};

export const getPaymentMethod = () => {

    const settings = useMonriComponentsIpsOtpData();

    const label = decodeEntities( settings.title ) || __( 'Monri Ips Otp', 'monri' );

    return {
        name: 'monri_components_ips_otp',
        label,
        ariaLabel: label,
        content: <IpsOtp />,
        edit: <IpsOtp />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


