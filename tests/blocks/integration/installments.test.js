import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import { Installments } from '../../../blocks/integration/installments';
import { __setSettings, __resetSettings } from '@woocommerce/settings';
import { __setMockSelectData, __resetMockSelectData } from '@wordpress/data';

describe('blocks/integration/installments.js', () => {
    let container = null;
    let root = null;

    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        root = createRoot(container);
        __resetSettings();
        __resetMockSelectData();
        global.wc.blocksCheckout.extensionCartUpdate.mockClear();
    });

    afterEach(() => {
        act(() => {
            root.unmount();
        });
        container.remove();
        container = null;
    });

    it('renders null when maximum installments is less than 1', () => {
        __setSettings({
            monri_data: {
                installments: 0,
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        expect(container.innerHTML).toBe('');
    });

    it('renders select with installment options when maximum installments >= 1', () => {
        __setSettings({
            monri_data: {
                installments: 4,
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        const select = container.querySelector('select');
        expect(select).not.toBeNull();

        const options = container.querySelectorAll('option');
        // Options should be: value 0 (No installments), value 2, value 3, value 4
        expect(options).toHaveLength(4);
        expect(options[0].value).toBe('0');
        expect(options[0].textContent).toBe('No installments');
        expect(options[1].value).toBe('2');
        expect(options[1].textContent).toBe('2');
        expect(options[2].value).toBe('3');
        expect(options[3].value).toBe('4');

        expect(global.wc.blocksCheckout.extensionCartUpdate).toHaveBeenCalledWith({
            namespace: 'monri-payments',
            data: {
                installments: 0,
            },
        });
    });

    it('updates installments when selection changes', () => {
        __setSettings({
            monri_data: {
                installments: 3,
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        const select = container.querySelector('select');

        act(() => {
            select.value = '3';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        expect(global.wc.blocksCheckout.extensionCartUpdate).toHaveBeenLastCalledWith({
            namespace: 'monri-payments',
            data: {
                installments: '3',
            },
        });
    });

    it('displays notice when installments fee is present in cart fees', () => {
        __setSettings({
            monri_data: {
                installments: 3,
            },
        });

        __setMockSelectData({
            cartData: {
                fees: [
                    { key: 'other_fee' },
                    { key: 'monri_installments_fee' },
                ],
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        const notice = container.querySelector('.installments-notice');
        expect(notice).not.toBeNull();
        expect(notice.textContent).toBe('An additional installments fee has been applied');
    });

    it('does not display notice when installments fee is not in cart fees', () => {
        __setSettings({
            monri_data: {
                installments: 3,
            },
        });

        __setMockSelectData({
            cartData: {
                fees: [{ key: 'other_fee' }],
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        const notice = container.querySelector('.installments-notice');
        expect(notice).toBeNull();
    });

    it('resets installments to 0 on unmount', () => {
        __setSettings({
            monri_data: {
                installments: 3,
            },
        });

        act(() => {
            root.render(<Installments />);
        });

        global.wc.blocksCheckout.extensionCartUpdate.mockClear();

        act(() => {
            root.unmount();
        });

        expect(global.wc.blocksCheckout.extensionCartUpdate).toHaveBeenCalledWith({
            namespace: 'monri-payments',
            data: {
                installments: 0,
            },
        });
    });
});
