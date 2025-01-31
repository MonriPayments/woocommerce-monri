import { getPaymentMethod as getWebPayForm } from './web-pay/form';
import { getPaymentMethod as getWebPayComponents } from './web-pay/components';
import { getPaymentMethod as getWebPayLightbox } from './web-pay/lightbox';
import { getPaymentMethod as getWsPayForm } from "./ws-pay/form";
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
