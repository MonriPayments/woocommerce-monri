import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import {
    WebPayForm,
    SavedTokenHandler,
    getPaymentMethod,
} from '../../../../blocks/integration/web-pay/form';
import { __setSettings, __resetSettings } from '@woocommerce/settings';

describe('blocks/integration/web-pay/form.js', () => {
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

    describe('getPaymentMethod', () => {
        it('returns payment method with form components and tokenization flags when supported', () => {
            __setSettings({
                monri_data: {
                    title: 'WebPay',
                    supports: ['products', 'tokenization'],
                },
            });

            const method = getPaymentMethod();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
            expect(method.edit).toBeDefined();
            expect(method.savedTokenComponent).toBeDefined();
            expect(method.supports.showSaveOption).toBe(true);
            expect(method.supports.showSavedCards).toBe(true);
        });

        it('does not enable save option when tokenization is not supported', () => {
            __setSettings({
                monri_data: {
                    title: 'WebPay',
                    supports: ['products'],
                },
            });

            const method = getPaymentMethod();
            expect(method.supports.showSaveOption).toBeUndefined();
            expect(method.supports.showSavedCards).toBeUndefined();
        });
    });

    describe('WebPayForm component', () => {
        it('renders description without installments when installments disabled', () => {
            __setSettings({
                monri_data: {
                    description: 'Pay securely via Monri &amp; partners',
                    installments: 0,
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<WebPayForm />);
            });

            expect(container.textContent).toContain('Pay securely via Monri & partners');
            expect(container.querySelector('select')).toBeNull();
        });

        it('renders empty string when description is missing', () => {
            __setSettings({
                monri_data: {
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<WebPayForm />);
            });

            expect(container.textContent).toBe('');
        });

        it('renders installments selector when installments enabled', () => {
            __setSettings({
                monri_data: {
                    description: 'Pay securely',
                    installments: 3,
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<WebPayForm />);
            });

            expect(container.textContent).toContain('Pay securely');
            expect(container.querySelector('select')).not.toBeNull();
        });
    });

    describe('SavedTokenHandler component', () => {
        it('renders description and installments', () => {
            __setSettings({
                monri_data: {
                    description: 'Pay using saved card',
                    installments: 2,
                    supports: ['products', 'tokenization'],
                },
            });

            act(() => {
                root.render(<SavedTokenHandler />);
            });

            expect(container.textContent).toContain('Pay using saved card');
            expect(container.querySelector('select')).not.toBeNull();
        });

        it('renders empty string when description is missing in SavedTokenHandler', () => {
            __setSettings({
                monri_data: {
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<SavedTokenHandler />);
            });

            expect(container.textContent).toBe('');
        });
    });
});
