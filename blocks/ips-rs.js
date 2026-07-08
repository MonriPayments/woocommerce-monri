import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useIpsRsIntegration} from "./integration";

const paymentMethod = useIpsRsIntegration();

registerPaymentMethod(paymentMethod);