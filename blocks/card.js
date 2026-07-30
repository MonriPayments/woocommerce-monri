import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useCardIntegration} from "./integration";

const paymentMethod = useCardIntegration();

registerPaymentMethod(paymentMethod);

