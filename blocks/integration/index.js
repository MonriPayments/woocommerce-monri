import { getPaymentMethod as getWebPayForm } from './web-pay/form';
import { getPaymentMethod as getWebPayComponents } from './web-pay/components';
import { getPaymentMethod as getWebPayLightbox } from './web-pay/lightbox';
import { getPaymentMethod as getWsPayForm } from "./ws-pay/form";
import { getPaymentMethod as getKeksPayForm } from "./components-additional-methods/keks-pay";
import { getPaymentMethod as getGooglePayForm } from "./components-additional-methods/google-pay";
import { getPaymentMethod as getApplePayForm } from "./components-additional-methods/apple-pay";
import { getPaymentMethod as getPayCekForm } from "./components-additional-methods/pay-cek";
import { useMonriData } from "./use-monri-data";

export const useIntegration = () => {
    const settings = useMonriData();

    switch (settings.service) {
        case 'monri-ws-pay':
            return getWsPayForm();
        case 'monri-web-pay':
            if (settings.integration_type === 'components') {
                return getWebPayComponents();
            } else if (settings.integration_type === 'lightbox') {
                return getWebPayLightbox();
            } else {
                return getWebPayForm();
            }
    }
};

export const useKeksIntegration = () => getKeksPayForm();

export const useGooglePayIntegration = () => getGooglePayForm();

export const useApplePayIntegration = () => getApplePayForm();

export const usePayCekIntegration = () => getPayCekForm();

