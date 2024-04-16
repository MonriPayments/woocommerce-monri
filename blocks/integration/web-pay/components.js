import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import {Fragment, useEffect, useRef, useId } from "react";
import Monri from "../../monri";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const WebPayComponents = (props) => {
    const settings = useMonriData();

    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const monriWrapperId = useId();

    const monriRef = useRef(null);
    const cardRef =  useRef(null);

    useEffect(() => {
        monriRef.current = Monri(settings.components.authenticity_token, {
            locale: settings.components.locale
        });

        const components = monriRef.current.components({clientSecret: settings.components.client_secret});

        cardRef.current = components.create('card', {style: {invalid: {color: 'red'}}, showInstallmentsSelection: settings.installments});
        cardRef.current.mount(monriWrapperId);
    }, []);

    const useComponentsTransaction = async () => {

        // @todo: get dynamically from billing addr
        const transactionParams = {
            address: 'test',
            fullName: 'test',
            city: 'test',
            zip: '31000',
            phone: '123456',
            country: 'HR',
            email: 'test@test.com'
        };

        const result = await monriRef.current.confirmPayment(cardRef.current, transactionParams);

        if (result.error) {
            throw new Error(result.error.message);
        } else if(result.result.status === 'approved') {
            return result.result;
        // handle declined on 3DS Cancel
        } else {
            throw new Error(__('Transaction declined, please reload the page.', 'monri'));
        }
    };

    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            try {
                const transaction = await useComponentsTransaction();

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'monri-transaction': JSON.stringify(transaction),
                        }
                    }
                };
            } catch (err) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: err.message,
                }
            }
        } );
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ] );

    return <Fragment>
        {decodeEntities( settings.description || '' )}
        <br />
        <div id={monriWrapperId} />
    </Fragment>;
};

export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayComponents />,
        edit: <WebPayComponents />,
    };
};