import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import {
    loadMonriData,
    WebPayLightbox,
    SavedTokenHandler,
    getPaymentMethod,
} from '../../../../blocks/integration/web-pay/lightbox';
import { __setSettings, __resetSettings } from '@woocommerce/settings';
import { __setMockSelectData, __resetMockSelectData } from '@wordpress/data';

describe('blocks/integration/web-pay/lightbox.js', () => {
    let container = null;
    let root = null;

    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        root = createRoot(container);
        __resetSettings();
        __resetMockSelectData();
    });

    afterEach(() => {
        act(() => {
            root.unmount();
        });
        container.remove();
        container = null;
        document.querySelectorAll('.wc-block-components-form').forEach((el) => el.remove());
        document.querySelectorAll('.lightbox-button').forEach((el) => el.remove());
    });

    describe('loadMonriData', () => {
        it('appends script with payment details attributes and clicks button on load', () => {
            const formContainer = document.createElement('div');
            formContainer.className = 'wc-block-components-form';
            document.body.appendChild(formContainer);

            const mockButton = document.createElement('button');
            mockButton.className = 'monri-lightbox-button-el';
            const clickSpy = jest.spyOn(mockButton, 'click');
            document.body.appendChild(mockButton);

            const paymentResult = {
                paymentDetails: {
                    src: 'https://ipgtest.monri.com/dist/components.js',
                    'data-authenticity-token': 'auth_123',
                    'data-amount': '1000',
                    'data-currency': 'EUR',
                    'data-order-number': 'ord_123',
                    'data-order-info': 'Order info',
                    'data-digest': 'digest_abc',
                    'data-transaction-type': 'purchase',
                    'data-language': 'en',
                    'data-success-url-override': 'https://example.com/success',
                    'data-cancel-url-override': 'https://example.com/cancel',
                    'data-callback-url-override': 'https://example.com/callback',
                    'data-ch-full-name': 'John Doe',
                    'data-ch-zip': '10000',
                    'data-ch-phone': '123456',
                    'data-ch-email': 'john@example.com',
                    'data-ch-address': 'Main street 1',
                    'data-ch-city': 'Zagreb',
                    'data-ch-country': 'HR',
                    'data-number-of-installments': '3',
                    'data-tokenize-pan': '1',
                    'data-supported-payment-methods': 'card',
                },
            };

            loadMonriData(paymentResult);

            const script = formContainer.querySelector('script.lightbox-button');
            expect(script).not.toBeNull();
            expect(script.src).toBe('https://ipgtest.monri.com/dist/components.js');
            expect(script.getAttribute('data-authenticity-token')).toBe('auth_123');
            expect(script.getAttribute('data-amount')).toBe('1000');
            expect(script.getAttribute('data-number-of-installments')).toBe('3');
            expect(script.getAttribute('data-tokenize-pan')).toBe('1');
            expect(script.getAttribute('data-supported-payment-methods')).toBe('card');

            // Trigger script.onload
            script.onload();
            expect(clickSpy).toHaveBeenCalled();

            // Trigger script.onerror
            script.onerror();
            expect(console).toHaveLoggedWith('something went wrong');

            mockButton.remove();
            formContainer.remove();
        });

        it('handles exceptions gracefully when form container is missing', () => {
            loadMonriData({ paymentDetails: { src: 'https://test.js' } });
            expect(console).toHaveErrored();
        });
    });

    describe('WebPayLightbox component', () => {
        it('renders description and triggers loadMonriData when checkout is complete', () => {
            __setSettings({
                monri_data: {
                    description: 'Lightbox description',
                    installments: 0,
                    supports: ['products'],
                },
            });

            const formContainer = document.createElement('div');
            formContainer.className = 'wc-block-components-form';
            document.body.appendChild(formContainer);

            __setMockSelectData({
                checkoutIsComplete: true,
                paymentResult: {
                    paymentDetails: {
                        src: 'https://ipgtest.monri.com/lightbox.js',
                        'data-authenticity-token': 'tok',
                    },
                },
            });

            act(() => {
                root.render(<WebPayLightbox />);
            });

            expect(container.textContent).toContain('Lightbox description');
            expect(formContainer.querySelector('script.lightbox-button')).not.toBeNull();

            formContainer.remove();
        });
    });

    describe('SavedTokenHandler component', () => {
        it('renders description and triggers loadMonriData when checkout is complete', () => {
            __setSettings({
                monri_data: {
                    description: 'Saved token lightbox',
                    installments: 0,
                    supports: ['products'],
                },
            });

            const formContainer = document.createElement('div');
            formContainer.className = 'wc-block-components-form';
            document.body.appendChild(formContainer);

            __setMockSelectData({
                checkoutIsComplete: true,
                paymentResult: {
                    paymentDetails: {
                        src: 'https://ipgtest.monri.com/lightbox.js',
                        'data-authenticity-token': 'tok_saved',
                    },
                },
            });

            act(() => {
                root.render(<SavedTokenHandler />);
            });

            expect(container.textContent).toContain('Saved token lightbox');
            expect(formContainer.querySelector('script.lightbox-button')).not.toBeNull();

            formContainer.remove();
        });
    });

    describe('getPaymentMethod', () => {
        it('returns payment configuration and enables tokenization flags when supported', () => {
            __setSettings({
                monri_data: {
                    title: 'Monri Lightbox',
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
    });
});
