import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useIpsOtpIntegration} from "./integration";

const paymentMethod = useIpsOtpIntegration();

registerPaymentMethod(paymentMethod);