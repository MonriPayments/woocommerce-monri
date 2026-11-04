import React from 'react';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';
import { __, setLocaleData, resetLocaleData } from '@wordpress/i18n';
import { __setSettings, __resetSettings } from '@woocommerce/settings';
import { __resetMockSelectData } from '@wordpress/data';
import { Installments } from '../../blocks/integration/installments';
import { ApplePay } from '../../blocks/integration/components-additional-methods/apple-pay';
import { GooglePay } from '../../blocks/integration/components-additional-methods/google-pay';
import { KeksPay } from '../../blocks/integration/components-additional-methods/keks-pay';
import { PayCek } from '../../blocks/integration/components-additional-methods/pay-cek';

const locales = ['bs_BA', 'de', 'hr', 'sr'];
const domain = 'monri';

describe('Block script translations', () => {
    let container = null;
    let root = null;

    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        root = createRoot(container);
        __resetSettings();
        __resetMockSelectData();
        resetLocaleData(undefined, domain);
    });

    afterEach(() => {
        act(() => {
            root.unmount();
        });
        container.remove();
        container = null;
        resetLocaleData(undefined, domain);
    });

    describe.each(locales)('Locale: %s', (locale) => {
        let translationJson;

        beforeAll(() => {
            translationJson = require(`../../languages/monri-${locale}-monri-wc-payments-blocks.json`);
        });

        it('has valid WordPress translation file structure', () => {
            expect(translationJson).toBeDefined();
            expect(translationJson.domain).toBe('messages');
            expect(translationJson.locale_data).toBeDefined();
            expect(translationJson.locale_data.messages).toBeDefined();

            const header = translationJson.locale_data.messages[''];
            expect(header).toBeDefined();
            expect(header.domain).toBe('messages');
            expect(header.lang).toBeDefined();
            expect(header['plural-forms']).toBeDefined();
        });

        it('loads translations into @wordpress/i18n for the monri text domain', () => {
            setLocaleData(translationJson.locale_data.messages, domain);

            const messages = translationJson.locale_data.messages;
            Object.keys(messages).forEach((msgKey) => {
                if (msgKey === '') {
                    return;
                }
                const expectedTranslation = messages[msgKey][0];
                expect(__(msgKey, domain)).toBe(expectedTranslation);
            });
        });
    });

    describe('Specific language translations', () => {
        it('correctly translates strings for German (de)', () => {
            const deJson = require('../../languages/monri-de-monri-wc-payments-blocks.json');
            setLocaleData(deJson.locale_data.messages, domain);

            expect(__('No installments', domain)).toBe('Keine Raten');
            expect(__('Number of installments: ', domain)).toBe('Anzahl der Raten: ');
            expect(__('Pay with Monri Apple Pay', domain)).toBe('Mit Monri Apple Pay bezahlen');
            expect(__('Pay with Monri Google Pay', domain)).toBe('Mit Monri Google Pay bezahlen');
            expect(__('Pay with Monri Keks', domain)).toBe('Mit Monri Keks bezahlen');
            expect(__('Pay with Monri PayCek', domain)).toBe('Mit Monri PayCek bezahlen');
            expect(__('Transaction declined, please reload the page.', domain))
                .toBe('Die Transaktion wurde abgelehnt. Bitte laden Sie die Seite neu.');
        });

        it('correctly translates strings for Croatian (hr)', () => {
            const hrJson = require('../../languages/monri-hr-monri-wc-payments-blocks.json');
            setLocaleData(hrJson.locale_data.messages, domain);

            expect(__('No installments', domain)).toBe('Bez rata');
            expect(__('Number of installments: ', domain)).toBe('Broj rata: ');
            expect(__('Pay with Monri Apple Pay', domain)).toBe('Plaćanje putem Monri Apple Paya');
            expect(__('Pay with Monri Google Pay', domain)).toBe('Plaćanje putem Monri Google Paya');
            expect(__('Pay with Monri Keks', domain)).toBe('Plaćanje putem Monri Keks Paya');
            expect(__('Pay with Monri PayCek', domain)).toBe('Plaćanje putem Monri PayCeka');
            expect(__('Transaction declined, please reload the page.', domain))
                .toBe('Transakcija odbijena, molimo Vas da osvježite stranicu.');
        });

        it('correctly translates strings for Serbian (sr)', () => {
            const srJson = require('../../languages/monri-sr-monri-wc-payments-blocks.json');
            setLocaleData(srJson.locale_data.messages, domain);

            expect(__('No installments', domain)).toBe('Bez rata');
            expect(__('Number of installments: ', domain)).toBe('Broj rata: ');
            expect(__('Pay with Monri Apple Pay', domain)).toBe('Plaćanje pomoću Apple Google Paya');
            expect(__('Pay with Monri Google Pay', domain)).toBe('Plaćanje pomoću Monri Google Paya');
            expect(__('Pay with Monri Keks', domain)).toBe('Plaćanje pomoću Monri Keks Paya');
            expect(__('Pay with Monri PayCek', domain)).toBe('Plaćanje pomoću Monri PayCeka');
            expect(__('Transaction declined, please reload the page.', domain))
                .toBe('Transakcija je odbijena, ponovo učitajte stranicu.');
        });

        it('correctly translates strings for Bosnian (bs_BA)', () => {
            const bsJson = require('../../languages/monri-bs_BA-monri-wc-payments-blocks.json');
            setLocaleData(bsJson.locale_data.messages, domain);

            expect(__('No installments', domain)).toBe('Bez rata');
            expect(__('Number of installments: ', domain)).toBe('Broj rata: ');
            expect(__('Pay with Monri Apple Pay', domain)).toBe('Plaćanje putem Monri Apple Paya');
            expect(__('Pay with Monri Google Pay', domain)).toBe('Plaćanje putem Monri Google Paya');
            expect(__('Pay with Monri Keks', domain)).toBe('Plaćanje putem Monri Keks Paya');
            expect(__('Pay with Monri PayCek', domain)).toBe('Plaćanje putem Monri PayCeka');
            expect(__('Transaction declined, please reload the page.', domain))
                .toBe('Transakcija odbijena, molimo Vas da osvježite stranicu.');
        });
    });

    describe('Component integration with loaded translations', () => {
        it('renders Installments component with German translations', () => {
            const deJson = require('../../languages/monri-de-monri-wc-payments-blocks.json');
            setLocaleData(deJson.locale_data.messages, domain);

            __setSettings({
                monri_data: {
                    installments: 3,
                },
            });

            act(() => {
                root.render(<Installments />);
            });

            const label = container.querySelector('label');
            expect(label.textContent).toBe('Anzahl der Raten: ');

            const options = container.querySelectorAll('option');
            expect(options[0].textContent).toBe('Keine Raten');
        });

        it('renders Installments component with Croatian translations', () => {
            const hrJson = require('../../languages/monri-hr-monri-wc-payments-blocks.json');
            setLocaleData(hrJson.locale_data.messages, domain);

            __setSettings({
                monri_data: {
                    installments: 3,
                },
            });

            act(() => {
                root.render(<Installments />);
            });

            const label = container.querySelector('label');
            expect(label.textContent).toBe('Broj rata: ');

            const options = container.querySelectorAll('option');
            expect(options[0].textContent).toBe('Bez rata');
        });

        it('renders payment method components with translated text in German', () => {
            const deJson = require('../../languages/monri-de-monri-wc-payments-blocks.json');
            setLocaleData(deJson.locale_data.messages, domain);

            act(() => {
                root.render(
                    <div>
                        <ApplePay />
                        <GooglePay />
                        <KeksPay />
                        <PayCek />
                    </div>
                );
            });

            expect(container.textContent).toContain('Mit Monri Apple Pay bezahlen');
            expect(container.textContent).toContain('Mit Monri Google Pay bezahlen');
            expect(container.textContent).toContain('Mit Monri Keks bezahlen');
            expect(container.textContent).toContain('Mit Monri PayCek bezahlen');
        });
    });
});
