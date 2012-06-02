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


	/* SHA algorithm */
	public function createHash ($sha1, $paramArray, $bSort = TRUE) {

		$upperFieldsArray = array_change_key_case($paramArray, CASE_UPPER);

		$shaFieldArray = array_keys($upperFieldsArray);
		if ($bSort) {
			asort($shaFieldArray);
		}
		$shaString = '';

		foreach ($shaFieldArray as $shaField) {

			$value = $upperFieldsArray[$shaField];
			$shaString .= $shaField . '=' . $value . $sha1;
		}
		$result = bin2hex(mhash(MHASH_SHA1, $shaString)); // Einmalige Zeichenkette zur Prüfung der Auftragsdaten.

		return $result;
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

		$fieldsArray = array();
		$fieldsArray = $this->config;

		if ($conf['PSPID'] != '') {
			$fieldsArray['PSPID'] = $conf['PSPID'];
		}
		if ($conf['SHA1'] != '') {
			$fieldsArray['SHA1'] = $conf['SHA1'];
		}

		$nFieldsArray = array();
		$nFieldsArray['orderID'] = $detailsArray['transaction']['orderuid'];
		$nFieldsArray['amount'] = $total['amounttax'] * 100;
		$nFieldsArray['currency'] = 'EUR';
		$nFieldsArray['language'] = 'de_DE';
		$nFieldsArray['CN'] = $address['person']['first_name'] . ' ' . $address['person']['last_name'];
		$nFieldsArray['EMAIL'] = $address['person']['email'];
		$nFieldsArray['owneraddress'] = $address['person']['address1'];
		$nFieldsArray['ownerZIP'] = $address['person']['zip'];
		$nFieldsArray['ownertown'] = $address['person']['city'];
		$nFieldsArray['ownercty'] = $address['person']['country'];
		$nFieldsArray['ownerteno'] = $address['person']['phone'];

		$nFieldsArray['Accepturl'] = $detailsArray['transaction']['successlink'];
		$nFieldsArray['Cancelurl'] = $detailsArray['transaction']['returi'];
		$nFieldsArray['Declineurl'] = $detailsArray['transaction']['faillink'];

		foreach ($nFieldsArray as $k => $v) {
			if ($v != '') {
				$fieldsArray[$k] = $v;
			}
		}

		// *******************************************************
		// Set article vars if selected
		// *******************************************************

		if (is_array($detailsArray) && isset($detailsArray['options']) && is_array($detailsArray['options'])) {
			foreach ($detailsArray['options'] as $k => $v) {
				if ($v != '') {
					$fieldsArray[$k] = $v;
				}
			}
		}

		$sha1 = $fieldsArray['SHA1'];
		unset($fieldsArray['SHA1']); // do not publish the secret key

		$fieldsArray['SHASign'] = $this->createHash($sha1, $fieldsArray); // Einmalige Zeichenkette zur Prüfung der Auftragsdaten.

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
				if ($reference == $theReference && $paramArray['NCERROR'] == 0) {
					$result['state'] = TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK;
					$result['amount'] = doubleval($paramArray['amount']);
					$result['message'] = $TYPO3_DB->fullQuoteStr(
						$paramArray['CN'] . ';' . $paramArray['BRAND'] . ';' . $paramArray['CARDNO'] . ';' . $paramArray['PAYID'] . ';' . $paramArray['ED'] . ';' . $paramArray['TRXDATE'],
						'tx_transactor_transactions');
				} else {
					$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK, 'Payment has failed. (' . $paramArray['NCERROR'] . ')');
				}
			} else {
				$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK, 'Bei der Bezahlung ist ein Fehler aufgetreten.');
			}
			$res = $TYPO3_DB->exec_UPDATEquery(
				'tx_transactor_transactions',
				'reference = ' . $TYPO3_DB->fullQuoteStr($reference, 'tx_transactor_transactions'),
				$result
			);
		} // error_transaction_no

		return $result;
	}


	public function transaction_failed ($resultsArray) {

		if ($resultsArray['status'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK)
			return TRUE;

		return FALSE;
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
			$paramTypeArray = array(
				'orderID', 'currency', 'amount', 'PM', 'ACCEPTANCE', 'STATUS', 'CARDNO',
				'ED', 'CN', 'TRXDATE', 'PAYID', 'NCERROR', 'BRAND', 'IPCTY', 'CCCTY',
				'ECI', 'CVCCheck', 'AAVCheck', 'VC', 'IP', 'SHASIGN'
			);

			foreach ($paramTypeArray as $type) {
				$paramArray[$type] = t3lib_div::_GP($type);
			}

			$sha1 = $conf['SHA1OUT'];

			$currentSHASign = $paramArray['SHASIGN'];
			unset($paramArray['SHASIGN']);
			$origSHASign = $this->createHash($sha1, $paramArray);

			if (
				$currentSHASign == strtoupper($origSHASign) &&
				$paramArray['IP'] == t3lib_div::getIndpEnv('REMOTE_ADDR')
			) {
				$result = $paramArray;
			}
		}
		return $result;
	} // readParams
}

?>