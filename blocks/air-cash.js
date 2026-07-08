import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useAirCashIntegration} from "./integration";

const paymentMethod = useAirCashIntegration();

registerPaymentMethod(paymentMethod);