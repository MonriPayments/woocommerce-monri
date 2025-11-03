import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useGooglePayIntegration} from "./integration";

const paymentMethod = useGooglePayIntegration();

registerPaymentMethod(paymentMethod);