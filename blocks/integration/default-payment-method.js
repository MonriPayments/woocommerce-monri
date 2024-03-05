import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMonriData } from "../data";

export const getDefaultPaymentMethod = () => {
    const settings = getMonriData();
    const label = decodeEntities( settings.title ) || __( 'Monri', 'monri' );

    return {
        name: 'monri',
        label,
        ariaLabel: label,
        canMakePayment: () => true,
        supports: {
            features: settings.supports,
        },
    };
};