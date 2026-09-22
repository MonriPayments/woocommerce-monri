import { useCartData } from '../../../blocks/integration/use-cart-data';
import { useSelect, __setMockSelectData, __resetMockSelectData } from '@wordpress/data';

describe('blocks/integration/use-cart-data.js', () => {
    beforeEach(() => {
        __resetMockSelectData();
    });

    it('retrieves cart data from wc/store/cart via useSelect', () => {
        const expectedCartData = {
            fees: [{ key: 'monri_installments_fee' }],
            billingAddress: {
                first_name: 'Test',
                last_name: 'User',
            },
            extensions: {
                'woocommerce-monri': {
                    client_secret: 'secret_abc',
                },
            },
        };

        __setMockSelectData({ cartData: expectedCartData });

        const cartData = useCartData();
        expect(useSelect).toHaveBeenCalled();
        expect(cartData).toEqual(expectedCartData);
    });
});
