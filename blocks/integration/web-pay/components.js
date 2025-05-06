import {Fragment, useEffect, useRef, useId, useState } from "react";
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import { useMonriData } from "../use-monri-data";
import { useCartData } from "../use-cart-data";
import Monri from "../../monri";
import { getDefaultPaymentMethod } from "../default-payment-method";

export const WebPayComponents = (props) => {
    const settings = useMonriData();

    const cartData = useCartData();

    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const monriWrapperId = useId();

    const monriRef = useRef(null);
    const cardRef =  useRef(null);
    const [clientSecret, setClientSecret] = useState(settings.components.client_secret);
    const contentRef = useRef(null);

    useEffect(() => {
        if (contentRef.current) {
            contentRef.current.innerHTML = '';
        }
        monriRef.current = Monri(settings.components.authenticity_token, {
            locale: settings.components.locale
        });

        const components = monriRef.current.components({clientSecret: clientSecret});

        cardRef.current = components.create('card', {style: {invalid: {color: 'red'}},
            showInstallmentsSelection: settings.installments,
            tokenizePanOffered: settings.tokenization});
        cardRef.current.mount(monriWrapperId);

    }, [clientSecret]);

    const useComponentsTransaction = async (billingAddress) => {
        const transactionParams = {
            address: billingAddress.address_1,
            fullName: `${billingAddress.first_name} ${billingAddress.last_name}`,
            city: billingAddress.city,
            zip: billingAddress.postcode,
            phone: billingAddress.phone,
            country: billingAddress.country,
            email: billingAddress.email,
        };

        for (const [field, value] of Object.entries(transactionParams)) {
            if (!Object.prototype.hasOwnProperty.call(transactionParams, field)) {
                continue;
            }

            if (value.toString().trim().length < 1) {
                throw new Error(sprintf(
                    __('%s is a required field', 'woocommerce'),
                    translatedFieldName(field)
                ));
            }
        }

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
        setClientSecret(cartData.extensions["woocommerce-monri"].client_secret);
        const unsubscribe = onPaymentSetup( async () => {
            try {
                const transaction = await useComponentsTransaction(cartData.billingAddress);

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
        cartData,
        onPaymentSetup,
    ] );

    return <Fragment>
        {decodeEntities( settings.description || '' )}
        <br />
        <div id={monriWrapperId} ref={contentRef}/>
    </Fragment>;
};

const translatedFieldName = (fieldName) => {
    let field = fieldName;
    switch (fieldName) {
        case 'address':
            field = __( 'Address', 'woocommerce' );
            break;
        case 'fullName':
            field = __( 'Name.', 'woocommerce' );
            break;
        case 'city':
            field = __( 'City', 'woocommerce' );
            break;
        case 'zip':
            field = __( 'Postal code', 'woocommerce' );
            break;
        case 'phone':
            field = __( 'Phone', 'woocommerce' );
            break;
        case 'country':
            field = __( 'Country/Region', 'woocommerce' );
            break;
        case 'email':
            field = __( 'Email address', 'woocommerce' );
            break;
    }

    return field;
};

export const getPaymentMethod = () => {
    return {
        ...getDefaultPaymentMethod(),
        content: <WebPayComponents />,
        edit: <WebPayComponents />,
    };
};