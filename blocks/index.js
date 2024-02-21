import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMonriData } from "./data";
import {useIntegration} from "./integration";

const settings = getMonriData();
const label = decodeEntities( settings.title ) || __( 'Monri', 'monri' );

const Content = useIntegration();

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