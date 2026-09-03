let mockSelectData = {};

const useSelect = jest.fn((mapSelect) => {
    const select = jest.fn((storeKey) => {
        if (storeKey === 'wc/store/cart') {
            return {
                getCartData: jest.fn(() => mockSelectData.cartData || {
                    fees: [],
                    billingAddress: {
                        address_1: 'Ilica 1',
                        first_name: 'John',
                        last_name: 'Doe',
                        city: 'Zagreb',
                        postcode: '10000',
                        phone: '+38591234567',
                        country: 'HR',
                        email: 'john.doe@example.com',
                    },
                    extensions: {
                        'woocommerce-monri': {
                            client_secret: 'test_client_secret_123',
                        },
                    },
                }),
            };
        }
        if (storeKey === 'wc/store/checkout') {
            return {
                isComplete: jest.fn(() => mockSelectData.checkoutIsComplete || false),
            };
        }
        if (storeKey === 'wc/store/payment') {
            return {
                getPaymentResult: jest.fn(() => mockSelectData.paymentResult || {}),
            };
        }
        return {};
    });
    return mapSelect(select);
});

const __setMockSelectData = (data) => {
    mockSelectData = { ...mockSelectData, ...data };
};

const __resetMockSelectData = () => {
    mockSelectData = {};
    useSelect.mockClear();
};

module.exports = {
    useSelect,
    __setMockSelectData,
    __resetMockSelectData,
};
