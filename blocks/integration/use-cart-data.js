import { useSelect } from "@wordpress/data";
const { CART_STORE_KEY } = wc.wcBlocksData;

export const useCartData = () => {
    return useSelect((select) => {
        const store = select(CART_STORE_KEY);

        return store.getCartData();
    });
};