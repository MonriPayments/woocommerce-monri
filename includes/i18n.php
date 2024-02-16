<?php

class Monri_WC_i18n {

	public static function get_translation($key = null)
	{
		// Monri_WC_Settings::instance()->get_option('language');
		// get WC/WP language?

		$lang = 'en';

		return call_user_func([self::class, "get_{$lang}_translation"], $key);
	}

	public static function get_en_translation()
	{
		$lang = array();

		//Credit card
		$lang['CARD_NUMBER'] = 'Card Number';
		$lang['EXPIRY'] = 'Expiry';
		$lang['CARD_CODE'] = 'Card Code';
		$lang['INSTALLMENTS_NUMBER'] = 'Number of installments';

		// Validation messages
		$lang['FIRST_NAME_ERROR'] = 'First name must have between 3 and 11 characters';
		$lang['LAST_NAME_ERROR'] = 'Last name must have between 3 and 28 characters';
		$lang['ADDRESS_ERROR'] = 'Address must have between 3 and 300 characters';
		$lang['CITY_ERROR'] = 'City must have between 3 and 30 characters';
		$lang['ZIP_ERROR'] = 'ZIP must have between 3 and 30 characters';
		$lang['PHONE_ERROR'] = 'Phone must have between 3 and 30 characters';
		$lang['EMAIL_ERROR'] = 'Email must have between 3 and 30 characters';

		$lang['CARD_NUMBER_ERROR'] = 'Card Number is emtpy';
		$lang['CARD_EXPIRY_ERROR'] = 'Card Expiry is emtpy';
		$lang['CARD_EXPIRY_ERROR_PAST'] = 'Card expiry is in past';
		$lang['CARD_CODE_ERROR'] = 'Card Code is emtpy';
		$lang['INVALID_CARD_NUMBER'] = 'Invalid Credit Card number';

		//Reciept page messages
		$lang['RECEIPT_PAGE'] = 'Thank you for your order, please click the button below to pay with Monri.';

		//Thankyou page messages
		$lang['THANK_YOU_SUCCESS'] = 'Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.';
		$lang['MONRI_SUCCESS'] = 'Monri payment successful<br/>Approval code: ';
		$lang['THANK_YOU_PENDING'] = 'Thank you for shopping with us. Right now your payment status is pending, We will keep you posted regarding the status of your order through e-mail';
		$lang['MONRI_PENDING'] = 'Monri payment status is pending<br/>Approval code: ';
		$lang['SECURITY_ERROR'] = 'Security Error. Illegal access detected';
		$lang['THANK_YOU_DECLINED'] = 'Thank you for shopping with us. However, the transaction has been declined.';
		$lang['THANK_YOU_DECLINED_NOTE'] = 'Transaction Declined: ';

		//Payment notes
		$lang['PAYMENT_COMPLETED'] = 'Monri payment completed.';
		$lang['TRANSACTION_FAILED'] = 'Transaction failed.';

		$lang['PAYMENT_INCREASE'] = 'Depending on the installments number chosen, the price will increase for';

		$lang['NUMBER_OF_INSTALLMENTS'] = 'Number of installments';
		$lang['MONRI_ORDER_AMOUNT'] = 'Monri - Order amount';

		return $lang;
	}

	public static function get_ba_hr_translation()
	{
		$lang = array();

		//Credit card
		$lang['CARD_NUMBER'] = 'Broj kartice';
		$lang['EXPIRY'] = 'Datum isteka';
		$lang['CARD_CODE'] = 'Cvv kod';
		$lang['INSTALLMENTS_NUMBER'] = 'Broj rata';

		// Validation messages
		$lang['FIRST_NAME_ERROR'] = 'Ime mora imati između 3 i 11 karaktera';
		$lang['LAST_NAME_ERROR'] = 'Prezime mora imati između 3 i 28 karaktera';
		$lang['ADDRESS_ERROR'] = 'Adresa mora imati između 3 i 300 karaktera';
		$lang['CITY_ERROR'] = 'Grad mora imati između 3 i 30 karaktera';
		$lang['ZIP_ERROR'] = 'Poštanski broj mora imati između 3 i 30 karaktera';
		$lang['PHONE_ERROR'] = 'Telefon mora imati između 3 i 30 karaktera';
		$lang['EMAIL_ERROR'] = 'Email mora imati između 3 i 30 karaktera';
		$lang['INVALID_CARD_NUMBER'] = 'Neispravan broj kreditne kartice';

		$lang['CARD_NUMBER_ERROR'] = 'Polje Broj kartice je prazno';
		$lang['CARD_EXPIRY_ERROR'] = 'Polje Datum isteka je prazno';
		$lang['CARD_EXPIRY_ERROR_PAST'] = 'Datum isteka je u prošlosti';
		$lang['CARD_CODE_ERROR'] = 'Polje Cvv kod je prazno';

		//Reciept page messages
		$lang['RECEIPT_PAGE'] = 'Zahvaljujemo se na vašoj narudžbi, kliknite da dugme ispod kako bi platili preko Monri-a.';

		//Thankyou page messages
		$lang['THANK_YOU_SUCCESS'] = 'Hvala što ste kupovali kod nas. Vaš račun je naplaćen i transakcija je uspješna. Uskoro ćemo vam poslati vašu narudžbu.';
		$lang['MONRI_SUCCESS'] = 'Monri plaćanje uspješno <br/>Approval code: ';
		$lang['THANK_YOU_PENDING'] = 'Hvala što ste kupovali kod nas. Trenutno vaš status plaćanja je na čekanju.';
		$lang['MONRI_PENDING'] = 'Monri plaćanje na čekanju<br/>Approval code: ';
		$lang['SECURITY_ERROR'] = 'Sigurnosna greška. Nedozvoljen pristup detektovan.';
		$lang['THANK_YOU_DECLINED'] = 'Hvala što ste kupovali kod nas. Nažalost transakcija je odbijena.';
		$lang['THANK_YOU_DECLINED_NOTE'] = 'Transakcija odbijena: ';

		//Payment notes
		$lang['PAYMENT_COMPLETED'] = 'Monri plaćanje uspješno.';
		$lang['TRANSACTION_FAILED'] = 'Transakcija neuspješna.';

		$lang['PAYMENT_INCREASE'] = 'Na osnovu odabranog broja rata cijena će se povećati za';

		$lang['NUMBER_OF_INSTALLMENTS'] = 'Broj rata';
		$lang['MONRI_ORDER_AMOUNT'] = 'Monri - Iznos narudžbe sa naknadom';

		return $lang;
	}


	/*
   ------------------
   Language: Srpski
   ------------------
   */
	public static function get_sr_translation()
	{
		$lang = array();

		//Credit card
		$lang['CARD_NUMBER'] = 'Broj kartice';
		$lang['EXPIRY'] = 'Datum isteka';
		$lang['CARD_CODE'] = 'Cvv kod';
		$lang['INSTALLMENTS_NUMBER'] = 'Broj rata';

		// Validation messages
		$lang['FIRST_NAME_ERROR'] = 'Ime mora da ima između 3 i 11 karaktera';
		$lang['LAST_NAME_ERROR'] = 'Prezime mora da ima između 3 i 28 karaktera';
		$lang['ADDRESS_ERROR'] = 'Adresa mora da ima između 3 i 300 karaktera';
		$lang['CITY_ERROR'] = 'Grad mora da ima između 3 i 30 karaktera';
		$lang['ZIP_ERROR'] = 'Poštanski broj mora da ima između 3 i 30 karaktera';
		$lang['PHONE_ERROR'] = 'Telefon mora da ima između 3 i 30 karaktera';
		$lang['EMAIL_ERROR'] = 'Email mora da ima između 3 i 30 karaktera';
		$lang['INVALID_CARD_NUMBER'] = 'Neispravan broj kreditne kartice';

		$lang['CARD_NUMBER_ERROR'] = 'Polje Broj kartice je prazno';
		$lang['CARD_EXPIRY_ERROR'] = 'Polje Datum isteka je prazno';
		$lang['CARD_EXPIRY_ERROR_PAST'] = 'Datum isteka je u prošlosti';
		$lang['CARD_CODE_ERROR'] = 'Polje Cvv kod je prazno';

		//Reciept page messages
		$lang['RECEIPT_PAGE'] = 'Zahvaljujemo se na vašoj narudžbi, kliknite da dugme ispod kako bi platili preko Monri-a.';

		//Thankyou page messages
		$lang['THANK_YOU_SUCCESS'] = 'Hvala što ste kupovali kod nas. Vaš račun je naplaćen i transakcija je uspešna. Uskoro ćemo vam poslati vašu narudžbu.';
		$lang['MONRI_SUCCESS'] = 'Monri plaćanje uspešno <br/>Approval code: ';
		$lang['THANK_YOU_PENDING'] = 'Hvala što ste kupovali kod nas. Trenutno vaš status plaćanja je na čekanju.';
		$lang['MONRI_PENDING'] = 'Monri plaćanje na čekanju<br/>Approval code: ';
		$lang['SECURITY_ERROR'] = 'Sigurnosna greška. Nedozvoljen pristup detektovan.';
		$lang['THANK_YOU_DECLINED'] = 'Hvala što ste kupovali kod nas. Nažalost transakcija je odbijena.';
		$lang['THANK_YOU_DECLINED_NOTE'] = 'Transakcija odbijena: ';

		//Payment notes
		$lang['PAYMENT_COMPLETED'] = 'Monri plaćanje uspešno.';
		$lang['TRANSACTION_FAILED'] = 'Transakcija neuspešna.';

		$lang['PAYMENT_INCREASE'] = 'Na osnovu odabranog broja rata cena će se povećati za';

		$lang['NUMBER_OF_INSTALLMENTS'] = 'Broj rata';
		$lang['MONRI_ORDER_AMOUNT'] = 'Monri - Iznos narudžbe sa naknadom';

		return $lang;
	}

	/*
------------------
Language: German
------------------
*/
	public static function get_de_translation()
	{
		$lang = array();

		//Credit card
		$lang['CARD_NUMBER'] = 'Kartennummer';
		$lang['EXPIRY'] = 'Ablaufdatum';
		$lang['CARD_CODE'] = 'CVV-Code';
		$lang['INSTALLMENTS_NUMBER'] = 'Anzahl der Raten';

		// Validation messages
		$lang['FIRST_NAME_ERROR'] = 'Der Vorname muss zwischen 3 und 11 Zeichen haben';
		$lang['LAST_NAME_ERROR'] = 'Der Nachname muss zwischen 3 und 28 Zeichen haben';
		$lang['ADDRESS_ERROR'] = 'Die Adresse muss zwischen 3 und 300 Zeichen haben';
		$lang['CITY_ERROR'] = 'Die Stadt muss zwischen 3 und 30 Zeichen haben';
		$lang['ZIP_ERROR'] = 'Die Postleitzahl muss zwischen 3 und 30 Zeichen haben';
		$lang['PHONE_ERROR'] = 'Die Telefonnummer muss zwischen 3 und 30 Zeichen haben';
		$lang['EMAIL_ERROR'] = 'Die E-Mail-Adresse muss zwischen 3 und 30 Zeichen haben';
		$lang['INVALID_CARD_NUMBER'] = 'Ungültige Kreditkartennummer';

		$lang['CARD_NUMBER_ERROR'] = 'Das Feld Kartennummer ist leer';
		$lang['CARD_EXPIRY_ERROR'] = 'Das Feld Ablaufdatum ist leer';
		$lang['CARD_EXPIRY_ERROR_PAST'] = 'Das Ablaufdatum liegt in der Vergangenheit';
		$lang['CARD_CODE_ERROR'] = 'Das Feld CVV-Code ist leer';

		//Receipt page messages
		$lang['RECEIPT_PAGE'] = 'Vielen Dank für Ihre Bestellung. Klicken Sie auf die Schaltfläche unten, um über Monri zu bezahlen.';

		//Thank you page messages
		$lang['THANK_YOU_SUCCESS'] = 'Vielen Dank für Ihren Einkauf bei uns. Ihre Rechnung wurde bezahlt, und die Transaktion war erfolgreich. Wir werden Ihre Bestellung bald versenden.';
		$lang['MONRI_SUCCESS'] = 'Monri-Zahlung erfolgreich <br/>Genehmigungscode: ';
		$lang['THANK_YOU_PENDING'] = 'Vielen Dank für Ihren Einkauf bei uns. Derzeit ist Ihr Zahlungsstatus ausstehend.';
		$lang['MONRI_PENDING'] = 'Monri-Zahlung ausstehend<br/>Genehmigungscode: ';
		$lang['SECURITY_ERROR'] = 'Sicherheitsfehler. Unberechtigter Zugriff erkannt.';
		$lang['THANK_YOU_DECLINED'] = 'Vielen Dank für Ihren Einkauf bei uns. Leider wurde die Transaktion abgelehnt.';
		$lang['THANK_YOU_DECLINED_NOTE'] = 'Transaktion abgelehnt: ';

		//Payment notes
		$lang['PAYMENT_COMPLETED'] = 'Monri-Zahlung erfolgreich.';
		$lang['TRANSACTION_FAILED'] = 'Transaktion fehlgeschlagen.';

		$lang['PAYMENT_INCREASE'] = 'Basierend auf der ausgewählten Anzahl von Raten wird der Preis um erhöht';

		$lang['NUMBER_OF_INSTALLMENTS'] = 'Anzahl der Raten';
		$lang['MONRI_ORDER_AMOUNT'] = 'Monri - Bestellbetrag mit Gebühr';

		return $lang;
	}


}