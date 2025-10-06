import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useApplePayIntegration} from "./integration";

const paymentMethod = useApplePayIntegration();

registerPaymentMethod(paymentMethod);