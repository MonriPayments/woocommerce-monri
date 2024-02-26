import { useState, useEffect, useMemo, useId } from 'react';
import { useMonriData } from "./use-monri-data";
import { __ } from '@wordpress/i18n';

const { extensionCartUpdate } = wc.blocksCheckout;

const useMaximumInstallments = () => {
    const data = useMonriData();

    return data.installments;
};

const updateInstallments = (installments) => {
    extensionCartUpdate({
        namespace: 'monri-payments',
        data: {
            installments
        }
    });
};

const useInstallmentOptions = (maximumInstallments) => {
    if (maximumInstallments < 1) {
        return [];
    }

    const options = [
        {
            label: __('No installments', 'monri'),
            value: 0,
        }
    ];

    for (let i = 2; i <= maximumInstallments; i++) {
        options.push({
            label: i,
            value: i,
        });
    }

    return options;
};

export const Installments = () => {
    const maximumInstallments = useMaximumInstallments();
    const [installments, setInstallments] = useState(0);

    const handleInstallmentsChange = (i) => setInstallments(i);

    useEffect(() => {
        updateInstallments(installments);
    }, [installments]);

    const installmentOptions = useMemo(
        () => useInstallmentOptions(maximumInstallments),
        [maximumInstallments]
    );

    const installmentsSelectorId = useId();

    if (installmentOptions.length < 1) {
        return null;
    }

    return (
        <div>
            <label htmlFor={installmentsSelectorId}>{__('Number of installments: ', 'monri')}</label>
            <select
                id={installmentsSelectorId}
                onChange={e => handleInstallmentsChange(e.target.value)}
                value={installments}
            >
                {installmentOptions.map(({ label, value }) =>
                    <option value={value} key={`installments-${value}`}>{label}</option>
                )}
            </select>
        </div>
    );
};