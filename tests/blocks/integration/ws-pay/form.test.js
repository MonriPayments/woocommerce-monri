import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import { WsPayForm, getPaymentMethod } from '../../../../blocks/integration/ws-pay/form';
import { __setSettings, __resetSettings } from '@woocommerce/settings';

describe('blocks/integration/ws-pay/form.js', () => {
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

    describe('WsPayForm component', () => {
        it('renders decoded description', () => {
            __setSettings({
                monri_data: {
                    description: 'Pay via WSPay gateway &amp; more',
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<WsPayForm />);
            });

            expect(container.textContent).toBe('Pay via WSPay gateway & more');
        });

        it('renders empty string when description is missing', () => {
            __setSettings({
                monri_data: {
                    supports: ['products'],
                },
            });

            act(() => {
                root.render(<WsPayForm />);
            });

            expect(container.textContent).toBe('');
        });
    });

    describe('getPaymentMethod', () => {
        it('returns payment method definition with WSPay form components', () => {
            __setSettings({
                monri_data: {
                    title: 'WSPay',
                    supports: ['products'],
                },
            });

            const method = getPaymentMethod();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
            expect(method.edit).toBeDefined();
            expect(method.supports.showSaveOption).toBeUndefined();
            expect(method.supports.showSavedCards).toBeUndefined();
        });

        it('enables save option and saved cards when tokenization supported', () => {
            __setSettings({
                monri_data: {
                    title: 'WSPay',
                    supports: ['products', 'tokenization'],
                },
            });

            const method = getPaymentMethod();
            expect(method.supports.showSaveOption).toBe(true);
            expect(method.supports.showSavedCards).toBe(true);
        });
    });
});
