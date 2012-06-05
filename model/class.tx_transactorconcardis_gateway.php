<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 *
 * This script handles payment via the ConCardis gateway.
 *
 *
 * ConCardis:	http://www.concardis.com
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor_concardis
 *
 *
 */

require_once (t3lib_extMgM::extPath('transactor') . 'model/class.tx_transactor_gateway.php');



class tx_transactorconcardis_gateway extends tx_transactor_gateway {
	protected $gatewayKey = 'transactor_concardis';
	protected $extKey = 'transactor_concardis';
	protected $supportedGatewayArray = array(TX_TRANSACTOR_GATEWAYMODE_FORM);
	protected $sendBasket = FALSE;	// Submit detailled basket informations like single products


		// Setup array for modifying the inputs
	public function __construct () {

		$conf = $this->getConf();
		$result = parent::__construct();
		$this->bSendBasket = $conf['sendBasket'];
		return $result;
	}


	/**
	 * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI () {
		$conf = $this->getConf();
		if ($this->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM) {
			$result = $conf['provideruri'] . 'orderstandard.asp';
		} else {
			$result = FALSE;
		}
		return $result;
	}


	// SHA Algorithm (Following Concardis-PDF Chapter 10.1)
	public function createHash ($sha1, $paramArray) {
		$shaString = '';

		// change keys into upper case letters
		$upperFieldsArray = array_change_key_case($paramArray, CASE_UPPER);

		// Sort the keys in alphabetical order
		$shaFieldsArray = $upperFieldsArray;
		ksort($shaFieldsArray, SORT_STRING);
		unset($upperFieldsArray);

		// Add the SHA Passphrase to FELD=VALUE
		foreach ($shaFieldsArray as $field => $value) {
			$shaString .= $field . '=' . $value . $sha1;
		}

		// unique character sequence vor the validation of the control data
		$digest = strtoupper(bin2hex(mhash(MHASH_SHA512, $shaString)));

		// Hint: Test using https://secure.payengine.de/ncol/test/testsha.asp

		return $digest;
	}


	/**
	 * Returns an array of field names and values which must be included as hidden
	 * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	array		Field names and values to be rendered as hidden fields
	 * @access	public
	 */
	public function transaction_formGetHiddenFields () {
		global $TSFE;

		$conf = $this->getConf();
		$detailsArray = $this->getDetails();
		$address = $detailsArray['address'];
		$total = $detailsArray['total'];
		$basket = $detailsArray['basket'];

		$description = array();
		foreach($basket as $itemId => $itemData) {
			$itemName = $itemData[0]['item_name'];
			$itemQuantitiy = $itemData[0]['quantity'];

			$description[] = $itemQuantitiy . "x - {$itemName} (ID: {$itemId})";
		}
		$description = implode(' ### ', $description);

		$fieldsArray = array();
		$fieldsArray = $this->config;

		// *************************************************************************************************************
		//	HINWEIS: Die Kommentare zu den Feldern sind aus der Implementierungs-PDF von Concardis!
		//
		//	HINWEIS 2: Das Layout wird erstmal nicht angepasst!
		// *************************************************************************************************************

		if ($conf['PSPID'] != '') {
			// Name Ihres Händlerkontos in unserem System.
			$fieldsArray['PSPID'] = $conf['PSPID'];
		}
		if ($conf['SHA1'] != '') {
			$fieldsArray['SHA1'] = $conf['SHA1'];
		}

		$nFieldsArray = array();

		// Ihre eindeutige Bestellnummer (Händlerreferenz). Das System sorgt dafür, dass für die gleiche Bestellung die
		// Zahlung nicht zweimal angefragt wird. Die orderID muss dynamisch zugewiesen werden.
		$nFieldsArray['orderID'] = $detailsArray['transaction']['orderuid'];

		// Zu zahlender Betrag, MULTIPLIZIERT MIT 100, da die Betragsangabe keine Dezimalstellen oder andere
		// Trennzeichen enthalten darf. Der Betrag muss dynamisch zugewiesen werden.
		$nFieldsArray['amount'] = $total['amounttax'] * 100;

		// Alphanumerischer Währungswert nach ISO, beispielsweise: EUR, USD, GBP, CHF, ...
		$nFieldsArray['currency'] = 'EUR';

		// Landessprache des Kunden, beispielsweise: en_US, nl_NL, fr_FR, ...
		$nFieldsArray['language'] = 'de_DE';

		// Name des Kunden. Wird aus dem Feld cardholder name der Kreditkartendaten übernommen und ist noch bearbeitbar.
		$nFieldsArray['CN'] = $address['person']['first_name'] . ' ' . $address['person']['last_name'];

		// E-Mail-Adresse des Kunden.
		$nFieldsArray['EMAIL'] = $address['person']['email'];

		// Straße und Hausnummer des Kunden.
		$nFieldsArray['owneraddress'] = $address['person']['address1'];

		// PLZ des Kunden.
		$nFieldsArray['ownerZIP'] = $address['person']['zip'];

		// Ortsname des Kunden.
		$nFieldsArray['ownertown'] = $address['person']['city'];

		// Land des Kunden.
		$nFieldsArray['ownercty'] = $address['person']['country'];

		// Telefonnummer des Kunden.
		$nFieldsArray['ownertelno'] = $address['person']['phone'];

		// Beschreibung der Bestellung.
		$nFieldsArray['COM'] = $description;

		// (Absolute) URL Ihres Katalogs. Wenn die Transaktion verarbeitet wurde, wird Ihr Kunde aufgefordert, durch
		// Anklicken einer Schaltfläche zu dieser URL zurückzukehren.
		$nFieldsArray['catalogurl'] = '';

		// (Absolute) URL Ihrer Homepage. Wenn die Transaktion verarbeitet wurde, wird Ihr Kunde aufgefordert, durch
		// Anklicken einer Schaltfläche zu dieser URL zurückzukehren.
		// Wenn Sie den Wert "NONE" senden, wird die Schaltfläche, die zur Händler-Webseite zurückführt, ausgeblendet.
		$nFieldsArray['homeurl'] = '';

		// URL der Webseite, die dem Kunden angezeigt werden soll, wenn die Zahlung autorisiert (Status 5), gespeichert
		// (Status 4) oder akzeptiert (Status 9) wurde oder auf die Annahme wartet (abwartend, Status 41, 51 oder 91).
		$nFieldsArray['accepturl'] = $detailsArray['transaction']['successlink'];

		// URL der Webseite, die dem Kunden angezeigt werden soll, wenn der Akzeptanzpartner die Autorisierung häufiger
		// als maximal zulässig verweigert hat (Status 2 oder 93).
		$nFieldsArray['declineurl'] = $detailsArray['transaction']['faillink'];

		// URL der Webseite, die dem Kunden angezeigt werden soll, wenn das Ergebnis des Zahlungsvorgangs unsicher ist
		// (Status 52 und 92).
		// Wenn dieses Feld leer ist, bekommt der Kunde stattdessen die accepturl angezeigt.
		$nFieldsArray['exceptionurl'] = '';

		// URL der Webseite, die dem Kunden angezeigt werden soll, wenn er den Zahlungsvorgang abbricht Status 1).
		// Wenn dieses Feld leer ist, bekommt der Kunde stattdessen die declineurl angezeigt.
		$nFieldsArray['cancelurl'] = $detailsArray['transaction']['returi'];

		// URL der Webseite, die dem Kunden angezeigt werden soll, wenn er auf unserer sicheren Zahlungsseite die
		// Zurück-Schaltfläche anklickt.
		$nFieldsArray['cancelurl'] = $detailsArray['transaction']['returi'];

		// Parameter ohne Inhalt fliegen raus
		foreach ($nFieldsArray as $key => $value) {

			if ($value != '') {
				$fieldsArray[$key] = $value;
			}
		}

		// *******************************************************
		// Set article vars if selected
		// *******************************************************

		if (
			is_array($detailsArray) &&
			isset($detailsArray['options']) &&
			is_array($detailsArray['options'])
		) {
			foreach ($detailsArray['options'] as $key => $value) {
				if ($value != '') {
					$fieldsArray[$key] = $value;
				}
			}
		}

		$sha1 = $fieldsArray['SHA1'];
		unset($fieldsArray['SHA1']); // do not publish the secret key

		// Setze SHA-IN-Signatur (Concardis-PDF-Kapitel 10.1)
		// -> Einmalige Zeichenkette zur Prüfung der Auftragsdaten.
		$fieldsArray['SHASign'] = $this->createHash($sha1, $fieldsArray);

		return $fieldsArray;
	}


	/**
	 * Returns the results of a processed transaction
	 *
	 * @param	string		$reference
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference) {
		global $TYPO3_DB;

		$result = array();
		if (($row = $this->getTransaction($reference)) === FALSE) {
			$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_IDLE, 'keine Transaktion gestartet');
		} else {
			$paramArray = $this->readParams();
			if (is_array($paramArray)) {
				$callExt = $this->getCallingExtension();
				$theReference = $this->generateReferenceUid($paramArray['orderID'], $callExt);

				// Wenn Status ok -> genehmigt
				if (
					$reference == $theReference &&
					( $paramArray['STATUS'] == 5 || $paramArray['STATUS'] == 9 )
				) {
					$result['state'] = TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK;
					$result['amount'] = doubleval($paramArray['amount']);
					$result['message'] = $TYPO3_DB->fullQuoteStr(
						$paramArray['CN'] . ';' . $paramArray['BRAND'] . ';' . $paramArray['CARDNO'] . ';' . $paramArray['PAYID'] . ';' . $paramArray['ED'] . ';' . $paramArray['TRXDATE'],
						'tx_transactor_transactions');
				} else {
					// Erhalte Status- und Fehlernachrichten
					$message = 'Status: {' . $paramArray['STATUS'] . '} - ' . $this->getStatusMessage($paramArray['STATUS']);

					if (!empty($paramArray['NCERROR'])) {
						$message .= ', Error: {' . $paramArray['NCERROR'] . '} - ' . $this->getErrorMessage($paramArray['NCERROR']);
					}
					$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK,
						'Payment has failed. ({' . $message . '})');
				}
			} else {
				$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_IDLE, 'Keine Daten an Concardis übermittelt.');
			}

			$res = $TYPO3_DB->exec_UPDATEquery(
				'tx_transactor_transactions',
				'reference = ' . $TYPO3_DB->fullQuoteStr($reference, 'tx_transactor_transactions'),
				$result
			);
		} // error_transaction_no

		return $result;
	}


	private function getErrorMessage($errorCode) {

		// Von: https://secure.payengine.de/ncol/paymentinfos5.asp
		require_once("include_error_codes.php");

		if(isset($errorCodes[$errorCode])) {
			return $errorCodes[$errorCode];
		}
		else {
			return "Unknown error!";
		}
 	}


	private function getStatusMessage($status) {

		// Von: https://secure.payengine.de/ncol/paymentinfos5.asp
		require_once("include_status_codes.php");

		if(isset($statusMessages[$status])) {
			return $statusMessages[$status];
		}
		else {
			return "Unknown status!";
		}
	}


	// *****************************************************************************
	// Helpers Return of payment parameters
	// *****************************************************************************
	public function readParams () {
		$result = '';

		$conf = $this->getConf();
		$orderID = t3lib_div::_GP('orderID');

		if ($orderID) {
			$paramArray = array();
// 			$paramTypeArray = array(
// 				'orderID', 'currency', 'amount', 'PM', 'ACCEPTANCE', 'STATUS', 'CARDNO',
// 				'ED', 'CN', 'TRXDATE', 'PAYID', 'NCERROR', 'BRAND', 'IPCTY', 'CCCTY',
// 				'ECI', 'CVCCheck', 'AAVCheck', 'VC', 'IP', 'SHASIGN'

			$paramTypeArray = array (
				'orderID',    // Ihre Bestellnummer
				'amount',     // Betrag der Bestellung (nicht mit 100 multipliziert)
				'currency',   // Währung der Bestellung
				'PM',         // Zahlungsmethode
				'ACCEPTANCE', // Vom Akzeptanzpartner zurückgesendeter Akzeptanzwert
				'STATUS',     // Transaktionsstatus (siehe Anhang 3 mit einem kurzen Statusüberblick)
				'CARDNO',     // Maskierte Kartennummer
				'PAYID',      // Bezahlungs ID als Referenz in unserem System
				'NCERROR',    // Fehlerwert
				'BRAND',      // Kartenmarke (unser System leitet sie von der Kartennummer ab)
				'ED',         // Kartenverfallsdatum
				'TRXDATE',    // Transaktionsdatum
				'CN',         // Name von Karteninhaber bzw. Kunde
				'IP',         // IP
				'SHASIGN',	  // Von unserem System berechneter SHA-Ausgangscode (wenn SHA-OUT
							  // konfiguriert ist)
				'complus',	  // Feld für die Einreichung eines Wertes, den Sie in der Post-Sale-
							  // Anfrage zurückgesendet haben möchten.
				'paramplus'	  // Feld für die Einreichung einiger Parameter und deren Werte, die
							  // Sie in der Post-Sale-Anfrage zurückgesendet haben möchten.
							  //
							  // Das Feld paramplus ist als solches nicht Bestandteil der Para-
							  // meter in der Rückmeldung. Stattdessen werden die Parameter bzw.
							  // Werte, die Sie in diesem Feld senden, analysiert und die sich
							  // daraus ergebenden Parameter werden der HTTP-Anfrage beigefügt.
			);

			foreach ($paramTypeArray as $type) {
				$paramArray[$type] = t3lib_div::_GP($type);
			}

			// Bearbeite nur gesetzte Werte
			$shaFieldsArray = array();
			foreach ($paramArray as $key => $value) {

				if ($value != '') {
					$shaFieldsArray[$key] = $value;
				}
			}

			$sha1 = $conf['SHA1OUT'];

			// Erhalte SHASign von Concardis
			$currentSHASign = $paramArray['SHASIGN'];
			unset($paramArray['SHASIGN']);
			unset($shaFieldsArray['SHASIGN']);

			// Führe Berechnung aus und überprüfe mit SHASign von Concardis
			$origSHASign = $this->createHash($sha1, $shaFieldsArray);
			unset($shaFieldsArray);

			if ($currentSHASign == $origSHASign) {
				$result = $paramArray;
 			}
		}
		return $result;
	} // readParams
}

?>