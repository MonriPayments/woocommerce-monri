import {
    useIntegration,
    useKeksIntegration,
    useGooglePayIntegration,
    useApplePayIntegration,
    usePayCekIntegration,
} from '../../../blocks/integration';
import { __setSettings, __resetSettings } from '@woocommerce/settings';

describe('blocks/integration/index.js', () => {
    beforeEach(() => {
        __resetSettings();
    });

    describe('useIntegration', () => {
        it('returns WsPayForm when service is monri-ws-pay', () => {
            __setSettings({
                monri_data: {
                    service: 'monri-ws-pay',
                    title: 'WSPay',
                    supports: ['products'],
                },
            });

            const method = useIntegration();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
        });

        it('returns Card form when service is monri-web-pay, components, and before_payment', () => {
            __setSettings({
                monri_data: {
                    service: 'monri-web-pay',
                    integration_type: 'components',
                    order_creation: 'before_payment',
                    title: 'Monri Card',
                    supports: ['products'],
                },
            });

            const method = useIntegration();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
        });

        it('returns WebPayComponents when service is monri-web-pay, components, and not before_payment', () => {
            __setSettings({
                monri_data: {
                    service: 'monri-web-pay',
                    integration_type: 'components',
                    order_creation: 'after_payment',
                    title: 'Monri WebPay',
                    supports: ['products'],
                    components: {
                        authenticity_token: 'tok_123',
                        locale: 'en',
                        client_secret: 'sec_123',
                        ip_address: '127.0.0.1',
                    },
                },
            });

            const method = useIntegration();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
        });

        it('returns WebPayLightbox when service is monri-web-pay and integration_type is lightbox', () => {
            __setSettings({
                monri_data: {
                    service: 'monri-web-pay',
                    integration_type: 'lightbox',
                    title: 'Monri Lightbox',
                    supports: ['products'],
                },
            });

            const method = useIntegration();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
            expect(method.savedTokenComponent).toBeDefined();
        });

        it('returns WebPayForm when service is monri-web-pay and integration_type is form', () => {
            __setSettings({
                monri_data: {
                    service: 'monri-web-pay',
                    integration_type: 'form',
                    title: 'Monri Form',
                    supports: ['products'],
                },
            });

            const method = useIntegration();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
            expect(method.savedTokenComponent).toBeDefined();
        });

        it('returns undefined when service is unrecognized', () => {
            __setSettings({
                monri_data: {
                    service: 'unknown-service',
                    supports: ['products'],
                },
            });

            const method = useIntegration();
            expect(method).toBeUndefined();
        });
    });

    describe('additional integration helpers', () => {
        it('returns keks pay payment method via useKeksIntegration', () => {
            __setSettings({
                monri_components_keks_pay_data: {
                    title: 'KEKS Pay',
                    supports: ['products'],
                },
            });

            const method = useKeksIntegration();
            expect(method.name).toBe('monri_components_keks_pay');
            expect(method.label).toBe('KEKS Pay');
        });

        it('returns google pay payment method via useGooglePayIntegration', () => {
            __setSettings({
                monri_components_google_pay_data: {
                    title: 'Google Pay',
                    supports: ['products'],
                },
            });

            const method = useGooglePayIntegration();
            expect(method.name).toBe('monri_components_google_pay');
            expect(method.label).toBe('Google Pay');
        });

        it('returns apple pay payment method via useApplePayIntegration', () => {
            __setSettings({
                monri_components_apple_pay_data: {
                    title: 'Apple Pay',
                    supports: ['products'],
                },
            });

            const method = useApplePayIntegration();
            expect(method.name).toBe('monri_components_apple_pay');
            expect(method.label).toBe('Apple Pay');
        });

        it('returns pay cek payment method via usePayCekIntegration', () => {
            __setSettings({
                monri_components_pay_cek_data: {
                    title: 'PayCek',
                    supports: ['products'],
                },
            });

            const method = usePayCekIntegration();
            expect(method.name).toBe('monri_components_pay_cek');
            expect(method.label).toBe('PayCek');
        });
    });
});
