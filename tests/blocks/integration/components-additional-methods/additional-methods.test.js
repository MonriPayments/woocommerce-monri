import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import {
    ApplePay,
    getPaymentMethod as getApplePayMethod,
} from '../../../../blocks/integration/components-additional-methods/apple-pay';
import {
    GooglePay,
    getPaymentMethod as getGooglePayMethod,
} from '../../../../blocks/integration/components-additional-methods/google-pay';
import {
    KeksPay,
    getPaymentMethod as getKeksPayMethod,
} from '../../../../blocks/integration/components-additional-methods/keks-pay';
import {
    PayCek,
    getPaymentMethod as getPayCekMethod,
} from '../../../../blocks/integration/components-additional-methods/pay-cek';
import {
    Card,
    getPaymentMethod as getCardMethod,
} from '../../../../blocks/integration/components-additional-methods/card';
import { __setSettings, __resetSettings } from '@woocommerce/settings';

describe('blocks/integration/components-additional-methods', () => {
    let container = null;
    let root = null;

    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        root = createRoot(container);
        __resetSettings();
    });

    afterEach(() => {
        act(() => {
            root.unmount();
        });
        container.remove();
        container = null;
    });

    describe('Apple Pay', () => {
        it('renders ApplePay component', () => {
            act(() => {
                root.render(<ApplePay />);
            });
            expect(container.textContent).toContain('Pay with Monri Apple Pay');
        });

        it('returns Apple Pay payment method configuration', () => {
            __setSettings({
                monri_components_apple_pay_data: {
                    title: 'Monri Apple Pay Test',
                    supports: ['products'],
                },
            });

            const method = getApplePayMethod();
            expect(method.name).toBe('monri_components_apple_pay');
            expect(method.label).toBe('Monri Apple Pay Test');
            expect(method.ariaLabel).toBe('Monri Apple Pay Test');
            expect(method.canMakePayment()).toBe(true);
            expect(method.supports.features).toEqual(['products']);
            expect(method.content).toBeDefined();
            expect(method.edit).toBeDefined();
        });

        it('falls back to default title if empty', () => {
            __setSettings({
                monri_components_apple_pay_data: {
                    title: '',
                    supports: ['products'],
                },
            });

            const method = getApplePayMethod();
            expect(method.label).toBe('Monri Apple Pay');
        });
    });

    describe('Google Pay', () => {
        it('renders GooglePay component', () => {
            act(() => {
                root.render(<GooglePay />);
            });
            expect(container.textContent).toContain('Pay with Monri Google Pay');
        });

        it('returns Google Pay payment method configuration', () => {
            __setSettings({
                monri_components_google_pay_data: {
                    title: 'Monri Google Pay Test',
                    supports: ['products'],
                },
            });

            const method = getGooglePayMethod();
            expect(method.name).toBe('monri_components_google_pay');
            expect(method.label).toBe('Monri Google Pay Test');
            expect(method.canMakePayment()).toBe(true);
            expect(method.supports.features).toEqual(['products']);
        });

        it('falls back to default title if empty', () => {
            __setSettings({
                monri_components_google_pay_data: {
                    title: '',
                    supports: ['products'],
                },
            });

            const method = getGooglePayMethod();
            expect(method.label).toBe('Monri Google Pay');
        });
    });

    describe('Keks Pay', () => {
        it('renders KeksPay component', () => {
            act(() => {
                root.render(<KeksPay />);
            });
            expect(container.textContent).toContain('Pay with Monri Keks');
        });

        it('returns Keks Pay payment method configuration', () => {
            __setSettings({
                monri_components_keks_pay_data: {
                    title: 'Monri Keks Test',
                    supports: ['products'],
                },
            });

            const method = getKeksPayMethod();
            expect(method.name).toBe('monri_components_keks_pay');
            expect(method.label).toBe('Monri Keks Test');
            expect(method.canMakePayment()).toBe(true);
            expect(method.supports.features).toEqual(['products']);
        });

        it('falls back to default title if empty', () => {
            __setSettings({
                monri_components_keks_pay_data: {
                    title: '',
                    supports: ['products'],
                },
            });

            const method = getKeksPayMethod();
            expect(method.label).toBe('Monri Keks');
        });
    });

    describe('PayCek', () => {
        it('renders PayCek component', () => {
            act(() => {
                root.render(<PayCek />);
            });
            expect(container.textContent).toContain('Pay with Monri PayCek');
        });

        it('returns PayCek payment method configuration', () => {
            __setSettings({
                monri_components_pay_cek_data: {
                    title: 'Monri PayCek Test',
                    supports: ['products'],
                },
            });

            const method = getPayCekMethod();
            expect(method.name).toBe('monri_components_pay_cek');
            expect(method.label).toBe('Monri PayCek Test');
            expect(method.canMakePayment()).toBe(true);
            expect(method.supports.features).toEqual(['products']);
        });

        it('falls back to default title if empty', () => {
            __setSettings({
                monri_components_pay_cek_data: {
                    title: '',
                    supports: ['products'],
                },
            });

            const method = getPayCekMethod();
            expect(method.label).toBe('Monri PayCek');
        });
    });

    describe('Card', () => {
        it('renders Card component with decoded description', () => {
            __setSettings({
                monri_data: {
                    description: 'Direct Card Payment &amp; more',
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<Card />);
            });
            expect(container.textContent).toContain('Direct Card Payment & more');
        });

        it('renders empty string when description is missing in Card', () => {
            __setSettings({
                monri_data: {
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<Card />);
            });
            expect(container.textContent).toBe('');
        });

        it('returns Card payment method configuration extending default payment method', () => {
            __setSettings({
                monri_data: {
                    title: 'Credit Card',
                    supports: ['products'],
                },
            });

            const method = getCardMethod();
            expect(method.name).toBe('monri');
            expect(method.label).toBe('Credit Card');
            expect(method.content).toBeDefined();
            expect(method.edit).toBeDefined();
        });
    });
});
