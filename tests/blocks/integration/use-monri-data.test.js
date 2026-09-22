import {
    useMonriData,
    useMonriComponentsKeksData,
    useMonriComponentsGooglePayData,
    useMonriComponentsApplePayData,
    useMonriComponentsPayCekData,
} from '../../../blocks/integration/use-monri-data';
import { __setSettings, __resetSettings } from '@woocommerce/settings';

describe('blocks/integration/use-monri-data.js', () => {
    beforeEach(() => {
        __resetSettings();
    });

    it('returns monri data via useMonriData', () => {
        const data = { service: 'monri-web-pay', title: 'Monri' };
        __setSettings({ monri_data: data });
        expect(useMonriData()).toEqual(data);
    });

    it('returns keks pay data via useMonriComponentsKeksData', () => {
        const keks = { title: 'KEKS Pay' };
        __setSettings({ monri_components_keks_pay_data: keks });
        expect(useMonriComponentsKeksData()).toEqual(keks);
    });

    it('returns google pay data via useMonriComponentsGooglePayData', () => {
        const gpay = { title: 'Google Pay' };
        __setSettings({ monri_components_google_pay_data: gpay });
        expect(useMonriComponentsGooglePayData()).toEqual(gpay);
    });

    it('returns apple pay data via useMonriComponentsApplePayData', () => {
        const apple = { title: 'Apple Pay' };
        __setSettings({ monri_components_apple_pay_data: apple });
        expect(useMonriComponentsApplePayData()).toEqual(apple);
    });

    it('returns pay cek data via useMonriComponentsPayCekData', () => {
        const paycek = { title: 'PayCek' };
        __setSettings({ monri_components_pay_cek_data: paycek });
        expect(useMonriComponentsPayCekData()).toEqual(paycek);
    });
});
