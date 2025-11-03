import { getSetting } from '@woocommerce/settings';

export const getMonriData = () => {
    const monriData = getSetting('monri_data', null);
    if (!monriData) {
        throw new Error("Monri settings not available");
    }

    return monriData;
};

export const getMonriComponentsKeksData = () => {
    return getSetting('monri_components_keks_pay_data', null);
};

export const getMonriComponentsGooglePayData = () => {
    return getSetting('monri_components_google_pay_data', null);
};

export const getMonriComponentsApplePayData = () => {
    return getSetting('monri_components_apple_pay_data', null);
};

export const getMonriComponentsPayCekData = () => {
    return getSetting('monri_components_pay_cek_data', null);
};