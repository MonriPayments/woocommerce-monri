import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import {
    WebPayComponents,
    getPaymentMethod,
} from '../../../../blocks/integration/web-pay/components';
import { __setSettings, __resetSettings } from '@woocommerce/settings';
import { __setMockSelectData, __resetMockSelectData } from '@wordpress/data';

describe('blocks/integration/web-pay/components.js', () => {
    let container = null;
    let root = null;
    let mockCard = null;
    let mockComponents = null;
    let mockMonriInstance = null;
    let registeredCallback = null;
    let unsubscribeMock = null;
    let props = null;

    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        root = createRoot(container);
        __resetSettings();
        __resetMockSelectData();

        __setSettings({
            monri_data: {
                title: 'Monri WebPay Components',
                description: 'Pay with card via Monri components',
                installments: 3,
                tokenization: true,
                supports: ['products'],
                components: {
                    authenticity_token: 'auth_token_xyz',
                    locale: 'hr',
                    client_secret: 'initial_secret_123',
                    ip_address: '192.168.1.1',
                },
            },
        });

        __setMockSelectData({
            cartData: {
                billingAddress: {
                    address_1: 'Ilica 1',
                    first_name: 'Marko',
                    last_name: 'Horvat',
                    city: 'Zagreb',
                    postcode: '10000',
                    phone: '+38591000000',
                    country: 'HR',
                    email: 'marko@example.com',
                },
                extensions: {
                    'woocommerce-monri': {
                        client_secret: 'cart_secret_456',
                    },
                },
            },
        });

        mockCard = {
            mount: jest.fn(),
        };

        mockComponents = {
            create: jest.fn(() => mockCard),
        };

        mockMonriInstance = {
            components: jest.fn(() => mockComponents),
            confirmPayment: jest.fn(() => Promise.resolve({
                result: { status: 'approved', id: 'tx_999' },
            })),
        };

        window.Monri.mockImplementation(() => mockMonriInstance);

        unsubscribeMock = jest.fn();
        registeredCallback = null;

        props = {
            eventRegistration: {
                onPaymentSetup: jest.fn((cb) => {
                    registeredCallback = cb;
                    return unsubscribeMock;
                }),
            },
            emitResponse: {
                responseTypes: {
                    SUCCESS: 'success',
                    ERROR: 'error',
                },
            },
        };
    });

    afterEach(() => {
        act(() => {
            root.unmount();
        });
        container.remove();
        container = null;
    });

    describe('getPaymentMethod', () => {
        it('returns payment method definition with components', () => {
            const method = getPaymentMethod();
            expect(method.name).toBe('monri');
            expect(method.content).toBeDefined();
            expect(method.edit).toBeDefined();
        });
    });

    describe('WebPayComponents rendering & lifecycle', () => {
        it('initializes Monri SDK, mounts card component, and subscribes to onPaymentSetup', () => {
            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            expect(container.textContent).toContain('Pay with card via Monri components');
            expect(window.Monri).toHaveBeenCalledWith('auth_token_xyz', { locale: 'hr' });
            expect(mockMonriInstance.components).toHaveBeenCalledWith({
                clientSecret: 'cart_secret_456',
            });
            expect(mockComponents.create).toHaveBeenCalledWith('card', {
                style: { invalid: { color: 'red' } },
                showInstallmentsSelection: 3,
                tokenizePanOffered: true,
            });
            expect(mockCard.mount).toHaveBeenCalled();
            expect(props.eventRegistration.onPaymentSetup).toHaveBeenCalled();
        });

        it('calls unsubscribe when unmounted', () => {
            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            expect(unsubscribeMock).not.toHaveBeenCalled();

            act(() => {
                root.unmount();
            });

            expect(unsubscribeMock).toHaveBeenCalled();
        });
    });

    describe('onPaymentSetup handler', () => {
        it('confirms payment successfully and returns SUCCESS response', async () => {
            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            expect(registeredCallback).toBeDefined();

            let response;
            await act(async () => {
                response = await registeredCallback();
            });

            expect(mockMonriInstance.confirmPayment).toHaveBeenCalled();
            const [passedCard, passedParams] = mockMonriInstance.confirmPayment.mock.calls[0];
            expect(passedCard).toBe(mockCard);
            expect(passedParams.address).toBe('Ilica 1');
            expect(passedParams.fullName).toBe('Marko Horvat');
            expect(passedParams.city).toBe('Zagreb');
            expect(passedParams.zip).toBe('10000');
            expect(passedParams.phone).toBe('+38591000000');
            expect(passedParams.country).toBe('HR');
            expect(passedParams.email).toBe('marko@example.com');
            expect(passedParams.browser_info).toBeDefined();
            expect(passedParams.browser_info.ip).toBe('192.168.1.1');

            expect(response).toEqual({
                type: 'success',
                meta: {
                    paymentMethodData: {
                        'monri-transaction': JSON.stringify({ status: 'approved', id: 'tx_999' }),
                    },
                },
            });
        });

        it.each([
            ['address_1', 'Address'],
            ['first_name', 'Name.'],
            ['city', 'City'],
            ['postcode', 'Postal code'],
            ['phone', 'Phone'],
            ['country', 'Country/Region'],
            ['email', 'Email address'],
        ])('returns ERROR when required field %s is missing', async (fieldKey, expectedLabel) => {
            const validBilling = {
                address_1: 'Ilica 1',
                first_name: 'Marko',
                last_name: 'Horvat',
                city: 'Zagreb',
                postcode: '10000',
                phone: '+38591000000',
                country: 'HR',
                email: 'marko@example.com',
            };

            const invalidBilling = { ...validBilling, [fieldKey]: '' };
            if (fieldKey === 'first_name') {
                invalidBilling.last_name = '';
            }

            __setMockSelectData({
                cartData: {
                    billingAddress: invalidBilling,
                    extensions: {
                        'woocommerce-monri': {
                            client_secret: 'cart_secret_456',
                        },
                    },
                },
            });

            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            let response;
            await act(async () => {
                response = await registeredCallback();
            });

            expect(response.type).toBe('error');
            expect(response.message).toContain(`${expectedLabel} is a required field`);
            expect(mockMonriInstance.confirmPayment).not.toHaveBeenCalled();
        });

        it('returns ERROR response when Monri confirmPayment returns an error object', async () => {
            mockMonriInstance.confirmPayment.mockResolvedValueOnce({
                error: { message: 'Card declined: Insufficient funds' },
            });

            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            let response;
            await act(async () => {
                response = await registeredCallback();
            });

            expect(response).toEqual({
                type: 'error',
                message: 'Card declined: Insufficient funds',
            });
        });

        it('returns ERROR response when transaction status is not approved', async () => {
            mockMonriInstance.confirmPayment.mockResolvedValueOnce({
                result: { status: 'declined' },
            });

            act(() => {
                root.render(<WebPayComponents {...props} />);
            });

            let response;
            await act(async () => {
                response = await registeredCallback();
            });

            expect(response).toEqual({
                type: 'error',
                message: 'Transaction declined, please reload the page.',
            });
        });
    });
});
