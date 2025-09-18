import {
    registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import {useGooglePayIntegration, useIntegration, useKeksIntegration, usePayCekIntegration} from "./integration";

const paymentMethod = useIntegration();
const keksPaymentMethod = useKeksIntegration();
const googlePayPaymentMethod = useGooglePayIntegration();
const payCekPaymentMethod = usePayCekIntegration();
//todo: separate this logic into 2 different files so that each payment method does not need to check settings for each payment method?
if (paymentMethod) {
    registerPaymentMethod(paymentMethod);
}

if (keksPaymentMethod) {
    registerPaymentMethod(keksPaymentMethod);
}

if (googlePayPaymentMethod) {
    registerPaymentMethod(googlePayPaymentMethod);
}

if (payCekPaymentMethod) {
    registerPaymentMethod(payCekPaymentMethod);
}