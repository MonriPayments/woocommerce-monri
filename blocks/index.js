import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';

const settings = window.wc.wcSettings.getSetting( 'monri_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Monri', 'monri' );

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