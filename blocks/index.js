import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import {useIntegration, useKeksIntegration} from "./integration";

const paymentMethod = useIntegration();
const KeksPaymentMethod = useKeksIntegration();
//todo: separate this logic into 2 different files?
registerPaymentMethod(paymentMethod);
registerPaymentMethod(KeksPaymentMethod);