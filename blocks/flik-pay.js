import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useFlikPayIntegration} from "./integration";

const paymentMethod = useFlikPayIntegration();

registerPaymentMethod(paymentMethod);