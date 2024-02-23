import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import {Fragment, useEffect, useRef, useId} from "react";
import Monri from "../../monri";
import {Installments} from "../installments";

export const WebPayComponents = (props) => {
    const settings = useMonriData();

    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const monriWrapperId = useId();

    const monriRef = useRef(null);
    const cardRef =  useRef(null);

    useEffect(() => {
        monriRef.current = Monri(settings.components.authenticity_token, {
            locale: 'hr' // todo
        });

        const components = monriRef.current.components(
            settings.components.random_token,
            settings.components.digest,
            settings.components.timestamp,
        );

        cardRef.current = components.create('card');
        cardRef.current.mount(monriWrapperId);
    }, []);

    const useComponentsToken = async () => {
        const result = await monriRef.current.createToken(cardRef.current);
        if (result.error) {
            throw new Error(result.error.message);
        } else {
            return result.result.id;
        }
    };

    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            try {
                const token = await useComponentsToken();

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'monri-token': token,
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
        <Installments />
        <div id={monriWrapperId} />
    </Fragment>;
};