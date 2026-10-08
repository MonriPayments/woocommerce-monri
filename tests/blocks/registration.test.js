import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __resetSettings } from '@woocommerce/settings';

describe('blocks entrypoint registration', () => {
    beforeEach(() => {
        __resetSettings();
        registerPaymentMethod.mockClear();
    });

    it('blocks/index.js registers the default payment method', () => {
        let registerMock;
        jest.isolateModules(() => {
            const { __setSettings } = require('@woocommerce/settings');
            const { registerPaymentMethod } = require('@woocommerce/blocks-registry');
            registerMock = registerPaymentMethod;
            __setSettings({
                monri_data: {
                    service: 'monri-ws-pay',
                    title: 'Monri Payment',
                    supports: ['products'],
                },
            });
            require('../../blocks/index');
        });

        expect(registerMock).toHaveBeenCalledTimes(1);
        const registered = registerMock.mock.calls[0][0];
        expect(registered.name).toBe('monri');
        expect(registered.label).toBe('Monri Payment');
    });

    it('blocks/apple-pay.js registers the apple pay payment method', () => {
        let registerMock;
        jest.isolateModules(() => {
            const { __setSettings } = require('@woocommerce/settings');
            const { registerPaymentMethod } = require('@woocommerce/blocks-registry');
            registerMock = registerPaymentMethod;
            __setSettings({
                monri_components_apple_pay_data: {
                    title: 'Apple Pay Registration',
                    supports: ['products'],
                },
            });
            require('../../blocks/apple-pay');
        });

        expect(registerMock).toHaveBeenCalledTimes(1);
        const registered = registerMock.mock.calls[0][0];
        expect(registered.name).toBe('monri_components_apple_pay');
        expect(registered.label).toBe('Apple Pay Registration');
    });

    it('blocks/google-pay.js registers the google pay payment method', () => {
        let registerMock;
        jest.isolateModules(() => {
            const { __setSettings } = require('@woocommerce/settings');
            const { registerPaymentMethod } = require('@woocommerce/blocks-registry');
            registerMock = registerPaymentMethod;
            __setSettings({
                monri_components_google_pay_data: {
                    title: 'Google Pay Registration',
                    supports: ['products'],
                },
            });
            require('../../blocks/google-pay');
        });

        expect(registerMock).toHaveBeenCalledTimes(1);
        const registered = registerMock.mock.calls[0][0];
        expect(registered.name).toBe('monri_components_google_pay');
        expect(registered.label).toBe('Google Pay Registration');
    });

    it('blocks/keks-pay.js registers the keks pay payment method', () => {
        let registerMock;
        jest.isolateModules(() => {
            const { __setSettings } = require('@woocommerce/settings');
            const { registerPaymentMethod } = require('@woocommerce/blocks-registry');
            registerMock = registerPaymentMethod;
            __setSettings({
                monri_components_keks_pay_data: {
                    title: 'KEKS Pay Registration',
                    supports: ['products'],
                },
            });
            require('../../blocks/keks-pay');
        });

        expect(registerMock).toHaveBeenCalledTimes(1);
        const registered = registerMock.mock.calls[0][0];
        expect(registered.name).toBe('monri_components_keks_pay');
        expect(registered.label).toBe('KEKS Pay Registration');
    });

    it('blocks/pay-cek.js registers the pay cek payment method', () => {
        let registerMock;
        jest.isolateModules(() => {
            const { __setSettings } = require('@woocommerce/settings');
            const { registerPaymentMethod } = require('@woocommerce/blocks-registry');
            registerMock = registerPaymentMethod;
            __setSettings({
                monri_components_pay_cek_data: {
                    title: 'PayCek Registration',
                    supports: ['products'],
                },
            });
            require('../../blocks/pay-cek');
        });

        expect(registerMock).toHaveBeenCalledTimes(1);
        const registered = registerMock.mock.calls[0][0];
        expect(registered.name).toBe('monri_components_pay_cek');
        expect(registered.label).toBe('PayCek Registration');
    });
});
