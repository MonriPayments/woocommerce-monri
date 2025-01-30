import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import { Fragment, useEffect } from "react";
import { getDefaultPaymentMethod } from "../default-payment-method";
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';


export const WebPayLightbox = () => {
    const settings = useMonriData();
    //https://github.com/woocommerce/woocommerce-blocks
    const {
        isComplete: checkoutIsComplete,
        orderId,
        store,
        customerId
    } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );
        return {
            isComplete: store.isComplete(),
            orderId : store.getOrderId(),
            store : store,
            customerId : store.getCustomerId()
        };
    } );

    useEffect(() => {
        const loadMonriData = async () => {
            if (checkoutIsComplete) {
                try {
                    console.log('store: ', store);
                    console.log('customer id:', customerId)
                    const response = await fetch(`/?rest_route=/monri/v1/order/${orderId}&customer_id=${customerId}`);
                    console.log('response: ', response);
                    const monri_data = await response.json();

                    const script = document.createElement("script");
                    if (!monri_data["src"]) {
                        console.log('invalid Monri data');
                        return;
                    }
                    script.src = monri_data["src"];
                    script.className = "lightbox-button";

                    script.setAttribute('data-authenticity-token', monri_data['data-authenticity-token']);
                    script.setAttribute('data-amount', monri_data['data-amount']);
                    script.setAttribute('data-currency', monri_data['data-currency']);
                    script.setAttribute('data-order-number', monri_data['data-order-number']);
                    script.setAttribute('data-order-info', monri_data['data-order-info']);
                    script.setAttribute('data-digest', monri_data['data-digest']);
                    script.setAttribute('data-transaction-type', monri_data['data-transaction-type']);
                    script.setAttribute('data-language', monri_data['data-language']);
                    script.setAttribute('data-success-url-override', monri_data['data-success-url-override']);
                    script.setAttribute('data-cancel-url-override', monri_data['data-cancel-url-override']);
                    script.setAttribute('data-ch-full-name', monri_data['data-ch-full-name']);
                    script.setAttribute('data-ch-zip', monri_data['data-ch-zip']);
                    script.setAttribute('data-ch-phone', monri_data['data-ch-phone']);
                    script.setAttribute('data-ch-email', monri_data['data-ch-email']);
                    script.setAttribute('data-ch-address', monri_data['data-ch-address']);
                    script.setAttribute('data-ch-city', monri_data['data-ch-city']);
                    script.setAttribute('data-ch-country', monri_data['data-ch-country']);


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
    </Fragment>;
};

export const getPaymentMethod = (payment) => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayLightbox />,
        edit: <WebPayLightbox />,
    };
};

