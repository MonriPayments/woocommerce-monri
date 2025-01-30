import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { Fragment, useEffect } from "react";
import { getDefaultPaymentMethod } from "../default-payment-method";
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY, PAYMENT_STORE_KEY, } from '@woocommerce/block-data';
import { Installments } from "../installments";


export const WebPayLightbox = () => {
    const settings = useMonriData();
    const showInstallments = settings.installments;
    //https://github.com/woocommerce/woocommerce-blocks
    const {
        isComplete: checkoutIsComplete,
        paymentResult,
    } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );
        const payment = select( PAYMENT_STORE_KEY );
        return {
            isComplete: store.isComplete(),
            paymentResult : payment.getPaymentResult(),
        };
    } );

    useEffect(() => {
        const loadMonriData = async () => {
            if (checkoutIsComplete) {
                try {
                    const monriData = paymentResult.paymentDetails;
                    let script = document.createElement('script');
                    script.src = monriData["src"];
                    script.className = "lightbox-button";

                    script.setAttribute('data-authenticity-token', monriData['data-authenticity-token']);
                    script.setAttribute('data-amount', monriData['data-amount']);
                    script.setAttribute('data-currency', monriData['data-currency']);
                    script.setAttribute('data-order-number', monriData['data-order-number']);
                    script.setAttribute('data-order-info', monriData['data-order-info']);
                    script.setAttribute('data-digest', monriData['data-digest']);
                    script.setAttribute('data-transaction-type', monriData['data-transaction-type']);
                    script.setAttribute('data-language', monriData['data-language']);
                    script.setAttribute('data-success-url-override', monriData['data-success-url-override']);
                    script.setAttribute('data-cancel-url-override', monriData['data-cancel-url-override']);
                    script.setAttribute('data-ch-full-name', monriData['data-ch-full-name']);
                    script.setAttribute('data-ch-zip', monriData['data-ch-zip']);
                    script.setAttribute('data-ch-phone', monriData['data-ch-phone']);
                    script.setAttribute('data-ch-email', monriData['data-ch-email']);
                    script.setAttribute('data-ch-address', monriData['data-ch-address']);
                    script.setAttribute('data-ch-city', monriData['data-ch-city']);
                    script.setAttribute('data-ch-country', monriData['data-ch-country']);

                    if( monriData['data-number-of-installments'] ) {
                        script.setAttribute('data-number-of-installments', monriData['data-number-of-installments']);
                    }

                    document.querySelector('.wc-block-components-form').appendChild(script);
                    script.onload = () => {
                        document.querySelector('button.monri-lightbox-button-el').click();
                    };

                    script.onerror = function() {
                        console.log('something went wrong');
                    }

                } catch (error) {
                    console.error('Error: ', error);
                }
            }
        };
        loadMonriData();
    }, [checkoutIsComplete]);

    return <Fragment>
        {decodeEntities(settings.description || '')}
        {showInstallments ? <Installments /> : ''}
    </Fragment>;
};

export const getPaymentMethod = (payment) => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayLightbox />,
        edit: <WebPayLightbox />,
    };
};

