import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import {useIntegration, useKeksIntegration} from "./integration";

const paymentMethod = useIntegration();
const keksPaymentMethod = useKeksIntegration();
//todo: separate this logic into 2 different files so that each payment method does not need to check settings for each payment method?
if (paymentMethod) {
    registerPaymentMethod(paymentMethod);
}

if (keksPaymentMethod) {
    registerPaymentMethod(keksPaymentMethod);
}