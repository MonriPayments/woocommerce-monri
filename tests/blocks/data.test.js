import {
    getMonriData,
    getMonriComponentsKeksData,
    getMonriComponentsGooglePayData,
    getMonriComponentsApplePayData,
    getMonriComponentsPayCekData,
} from '../../blocks/data';
import { __setSettings, __resetSettings, getSetting } from '@woocommerce/settings';

describe('blocks/data.js', () => {
    beforeEach(() => {
        __resetSettings();
    });

    describe('getMonriData', () => {
        it('throws an error if monri_data setting is not available', () => {
            expect(() => getMonriData()).toThrow('Monri settings not available');
            expect(getSetting).toHaveBeenCalledWith('monri_data', null);
        });

        it('returns monri_data setting when available', () => {
            const sampleData = { service: 'monri-web-pay', title: 'Monri' };
            __setSettings({ monri_data: sampleData });
            expect(getMonriData()).toEqual(sampleData);
        });
    });

    describe('getMonriComponentsKeksData', () => {
        it('returns null if setting is not present', () => {
            expect(getMonriComponentsKeksData()).toBeNull();
            expect(getSetting).toHaveBeenCalledWith('monri_components_keks_pay_data', null);
        });

        it('returns setting value when present', () => {
            const keksData = { title: 'KEKS Pay' };
            __setSettings({ monri_components_keks_pay_data: keksData });
            expect(getMonriComponentsKeksData()).toEqual(keksData);
        });
    });

    describe('getMonriComponentsGooglePayData', () => {
        it('returns null if setting is not present', () => {
            expect(getMonriComponentsGooglePayData()).toBeNull();
            expect(getSetting).toHaveBeenCalledWith('monri_components_google_pay_data', null);
        });

        it('returns setting value when present', () => {
            const gpayData = { title: 'Google Pay' };
            __setSettings({ monri_components_google_pay_data: gpayData });
            expect(getMonriComponentsGooglePayData()).toEqual(gpayData);
        });
    });

    describe('getMonriComponentsApplePayData', () => {
        it('returns null if setting is not present', () => {
            expect(getMonriComponentsApplePayData()).toBeNull();
            expect(getSetting).toHaveBeenCalledWith('monri_components_apple_pay_data', null);
        });

        it('returns setting value when present', () => {
            const appleData = { title: 'Apple Pay' };
            __setSettings({ monri_components_apple_pay_data: appleData });
            expect(getMonriComponentsApplePayData()).toEqual(appleData);
        });
    });

    describe('getMonriComponentsPayCekData', () => {
        it('returns null if setting is not present', () => {
            expect(getMonriComponentsPayCekData()).toBeNull();
            expect(getSetting).toHaveBeenCalledWith('monri_components_pay_cek_data', null);
        });

        it('returns setting value when present', () => {
            const payCekData = { title: 'PayCek' };
            __setSettings({ monri_components_pay_cek_data: payCekData });
            expect(getMonriComponentsPayCekData()).toEqual(payCekData);
        });
    });
});
