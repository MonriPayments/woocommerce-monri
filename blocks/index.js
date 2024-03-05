import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import { useIntegration } from "./integration";

const paymentMethod = useIntegration();

registerPaymentMethod(paymentMethod);