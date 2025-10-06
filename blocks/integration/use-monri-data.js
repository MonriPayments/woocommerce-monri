import {getMonriData, getMonriComponentsKeksData, getMonriComponentsGooglePayData, getMonriComponentsPayCekData, getMonriComponentsApplePayData} from "../data";

export const useMonriData = () => {
    return getMonriData();
};

export const useMonriComponentsKeksData = () => {
    return getMonriComponentsKeksData();
};

export const useMonriComponentsGooglePayData = () => {
    return getMonriComponentsGooglePayData();
};

export const useMonriComponentsApplePayData = () => {
    return getMonriComponentsApplePayData();
};

export const useMonriComponentsPayCekData = () => {
    return getMonriComponentsPayCekData();
};