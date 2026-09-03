import { getDefaultPaymentMethod } from '../../../blocks/integration/default-payment-method';
import { __setSettings, __resetSettings } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

describe('blocks/integration/default-payment-method.js', () => {
    beforeEach(() => {
        __resetSettings();
    });

    it('returns default payment method configuration with custom title', () => {
        __setSettings({
            monri_data: {
                title: 'Credit Card (Monri)',
                supports: ['products', 'tokenization'],
            },
        });

        const method = getDefaultPaymentMethod();

        expect(method.name).toBe('monri');
        expect(method.label).toBe('Credit Card (Monri)');
        expect(method.ariaLabel).toBe('Credit Card (Monri)');
        expect(typeof method.canMakePayment).toBe('function');
        expect(method.canMakePayment()).toBe(true);
        expect(method.supports.features).toEqual(['products', 'tokenization']);
    });

    it('falls back to default title "Monri" if title is empty or not set', () => {
        __setSettings({
            monri_data: {
                title: '',
                supports: ['products'],
            },
        });

        const method = getDefaultPaymentMethod();

        expect(method.label).toBe('Monri');
        expect(method.ariaLabel).toBe('Monri');
    });

    it('decodes HTML entities in the title', () => {
        __setSettings({
            monri_data: {
                title: 'Pay &amp; Go',
                supports: ['products'],
            },
        });

        const method = getDefaultPaymentMethod();

        expect(decodeEntities).toHaveBeenCalledWith('Pay &amp; Go');
        expect(method.label).toBe('Pay & Go');
    });
});
