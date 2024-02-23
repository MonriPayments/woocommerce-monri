import { WebPayForm } from './web-pay/form';
import { WebPayComponents } from './web-pay/components';
import { useMonriData } from "./use-monri-data";
import { WsPayRedirect } from "./ws-pay/redirect";

export const useIntegration = () => {
    const settings = useMonriData();
    switch (settings.service) {
        case 'monri-ws-pay':
            return WsPayRedirect;
        case 'monri-web-pay':
            if (settings.integration_type === 'components') {
                return WebPayComponents;
            }

            return WebPayForm;
    }
};
