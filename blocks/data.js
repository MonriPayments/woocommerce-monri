import { getSetting } from '@woocommerce/settings';

export const getMonriData = () => {
    const monriData = getSetting('monri_data', null);
    if (!monriData) {
        throw new Error("Monri settings not available");
    }

    return monriData;
};