import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {usePayCekIntegration} from "./integration";

const paymentMethod = usePayCekIntegration();

registerPaymentMethod(paymentMethod);