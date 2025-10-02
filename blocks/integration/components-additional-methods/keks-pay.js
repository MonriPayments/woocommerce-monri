import { decodeEntities } from '@wordpress/html-entities';
import { useMonriComponentsKeksData } from "../use-monri-data";
import {Fragment} from "react";
import { __ } from '@wordpress/i18n';


export const KeksPay = () => {
    return <Fragment>
        {decodeEntities(__('Pay with Monri Keks', 'monri'))}
    </Fragment>;
};


export const getPaymentMethod = () => {

    const settings = useMonriComponentsKeksData();

    const label = decodeEntities( settings.title ) || __( 'Monri Keks', 'monri' );

    return {
        name: 'monri_components_keks_pay',
        label,
        ariaLabel: label,
        content: <KeksPay />,
        edit: <KeksPay />,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};


