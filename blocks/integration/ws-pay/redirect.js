import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";

export const WsPayRedirect = () => {
    const settings = useMonriData();

    return decodeEntities(settings.description || '');
};