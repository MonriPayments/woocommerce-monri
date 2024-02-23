import { decodeEntities } from '@wordpress/html-entities';
import { useMonriData } from "../use-monri-data";
import {Fragment} from "react";
import {Installments} from "../installments";

export const WebPayForm = () => {
    const settings = useMonriData();

    return <Fragment>
        {decodeEntities(settings.description || '')}
        <Installments />
    </Fragment>;
};