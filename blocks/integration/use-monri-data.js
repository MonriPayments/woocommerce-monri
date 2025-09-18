import {getMonriData, getMonriComponentsKeksData, getMonriComponentsGooglePayData, getMonriComponentsPayCekData} from "../data";

export const useMonriData = () => {
    return getMonriData();
};

export const useMonriComponentsKeksData = () => {
    return getMonriComponentsKeksData();
};

export const useMonriComponentsGooglePayData = () => {
    return getMonriComponentsGooglePayData();
};

export const useMonriComponentsPayCekData = () => {
    return getMonriComponentsPayCekData();
};