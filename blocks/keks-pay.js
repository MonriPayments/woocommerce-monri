import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useKeksIntegration} from "./integration";

const paymentMethod = useKeksIntegration();

registerPaymentMethod(paymentMethod);