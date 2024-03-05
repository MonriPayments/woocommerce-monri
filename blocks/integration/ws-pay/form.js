import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const WsPayForm = () => {
    const settings = useMonriData();

    return decodeEntities(settings.description || '');
};

export const getPaymentMethod = () => {
    const payment = {
        ...getDefaultPaymentMethod(),
        content: <WsPayForm />,
        edit: <WsPayForm />,
    }

    if (useMonriData().supports.indexOf('tokenization') !== -1) {
        payment.supports.showSaveOption = true;
        payment.supports.showSavedCards = true;
    }

    return payment;
};