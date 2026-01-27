import {getMonriData, getMonriComponentsKeksData, getMonriComponentsGooglePayData, getMonriComponentsPayCekData, getMonriComponentsApplePayData, getMonriComponentsAirCashData, getMonriComponentsFlikPayData} from "../data";

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

export const useMonriComponentsAirCashData = () => {
    return getMonriComponentsAirCashData();
};

export const useMonriComponentsFlikPayData = () => {
    return getMonriComponentsFlikPayData();
};