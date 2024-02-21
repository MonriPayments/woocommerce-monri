import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMonriSettings } from "./settings";

const settings = getMonriSettings();
const label = decodeEntities( settings.title ) || __( 'Monri', 'monri' );

const Content = () => {
    return decodeEntities(settings.description || '');
};

registerPaymentMethod({
    name: 'monri',
    label,
    ariaLabel: label,
    canMakePayment: () => true,
    content: <Content />,
    edit: <Content />,
    supports: {
        features: settings.supports,
    }
});