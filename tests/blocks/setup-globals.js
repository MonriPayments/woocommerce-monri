global.IS_REACT_ACT_ENVIRONMENT = true;

global.wc = {
    blocksCheckout: {
        extensionCartUpdate: jest.fn(),
    },
    wcBlocksData: {
        CART_STORE_KEY: 'wc/store/cart',
    },
};

const createMockMonriInstance = () => {
    const cardComponent = {
        mount: jest.fn(),
    };

    const components = {
        create: jest.fn((type, options) => cardComponent),
    };

    return {
        components: jest.fn((options) => components),
        confirmPayment: jest.fn((component, params) => Promise.resolve({
            result: { status: 'approved' },
        })),
        _cardComponent: cardComponent,
        _components: components,
    };
};

const mockMonri = jest.fn((token, options) => createMockMonriInstance());
mockMonri._createMockMonriInstance = createMockMonriInstance;

global.window.Monri = mockMonri;
global.Monri = mockMonri;
