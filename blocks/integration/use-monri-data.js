import {getMonriData, getMonriComponentsKeksData, getMonriComponentsGooglePayData} from "../data";

export const useMonriData = () => {
    return getMonriData();
};

export const useMonriComponentsKeksData = () => {
    return getMonriComponentsKeksData();
};

export const useMonriComponentsGooglePayData = () => {
    return getMonriComponentsGooglePayData();
};