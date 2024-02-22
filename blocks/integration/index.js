import { MonriRedirect } from './monri-web-pay/redirect';
import { MonriComponents } from './monri-web-pay/components';
import {useMonriData} from "./use-monri-data";

export const useIntegration = () => {
    const settings = useMonriData();
    switch (settings.service) {
        case 'monri-ws-pay':
            return null; // todo: bbutkovic
        case 'monri-web-pay':
            if (settings.integration_type === 'components') {
                return MonriComponents;
            }

            return MonriRedirect;
    }
};
