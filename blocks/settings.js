import { getSetting } from '@woocommerce/settings';

export const getMonriSettings = () => {
    const monriSettings = getSetting('monri_data', null);
    if (!monriSettings) {
        throw new Error("Monri settings not available");
    }

    return monriSettings;
};