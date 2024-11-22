import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { getDefaultPaymentMethod } from "../default-payment-method";
import { Installments } from "../installments";
import { Fragment } from "react";

export const WsPayForm = () => {
    const settings = useMonriData();

    const showInstallments = settings.installments;
    return <Fragment>
        {decodeEntities(settings.description || '')}
        {showInstallments ? <Installments /> : ''}
    </Fragment>;
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