import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { Fragment } from "react";
import { Installments } from "../installments";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const WebPayForm = () => {
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
        content: <WebPayForm />,
        edit: <WebPayForm />,
    }

    if (useMonriData().supports.indexOf('tokenization') !== -1) {
        payment.supports.showSaveOption = true;
        payment.supports.showSavedCards = true;
    }

    return payment;
};