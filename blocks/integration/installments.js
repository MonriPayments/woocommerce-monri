import { useState, useEffect, useId } from 'react';
import { useMonriData } from "./use-monri-data";
import { useDispatch, useSelect } from '@wordpress/data';
import { select } from "@woocommerce/block-data";

const { extensionCartUpdate } = wc.blocksCheckout;
const { CART_STORE_KEY } = wc.wcBlocksData;

const useMaximumInstallments = () => {
    const data = useMonriData();
    // todo
    return 12;
};

const useInitialInstallments = () => {
    const data = useMonriData();

    return 0;
};

const useCartData = () => {
    return useSelect((select) => {
        const store = select(CART_STORE_KEY);

        return store.getCartData();
    });
};

const useInstallmentsFee = () => {
    const cartData = useCartData();

    return [...cartData.fees].find((fee) => fee.key === 'monri_installments_fee') || null;
};

const updateInstallments = (installments) => {
    extensionCartUpdate({
        namespace: 'monri-payments',
        data: {
            installments
        }
    });
};

const formatInstallmentsFee = (fee) => {
      // todo: check if there is a built-in helper?

    const feeCents = parseInt(fee);

    return feeCents/100.0;
};

export const Installments = () => {
    const maximumInstallments = useMaximumInstallments();
    const initialInstallments = useInitialInstallments();
    const [installments, setInstallments] = useState(initialInstallments);

    const handleInstallmentsChange = (i) => setInstallments(i);

    useEffect(() => {
        updateInstallments(installments);
    }, [installments]);

    const installmentOptions = [...new Array(maximumInstallments + 1)]
        .map((_, index) => {
            return <option value={index} key={`installments-${index}`}>{index}</option>;
        });

    const installmentsSelectorId = useId();

    const installmentsFee = useInstallmentsFee();

    return (
        <div>
            <label htmlFor={installmentsSelectorId}>Installments: </label>
            <select
                id={installmentsSelectorId}
                onChange={e => handleInstallmentsChange(e.target.value)}
                value={installments}
            >
                {installmentOptions}
            </select>
            {installmentsFee &&
                <div className="installments-fee-notice">
                    An additional fee of {formatInstallmentsFee(installmentsFee.totals.total)} will be applied to your order
                </div>
            }
        </div>
    );
};