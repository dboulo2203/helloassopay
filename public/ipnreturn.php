<?php
/**
 * IPN return from Hello asso,
 * Helloasso send a message for each payment. This function log the helloasso ipn response mainly for log
 * the record is  
 * 
 */
/**
 *	\file       payplug/payplugindex.php
 *	\ingroup    payplug
 *	\brief      Home page of payplug top menu
 */
	// Dolibarr paramters
	if (!defined('NOLOGIN')) {
		define("NOLOGIN", 1); // This means this output page does not require to be logged.
	}
	if (!defined('NOCSRFCHECK')) {
		define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	}
	if (!defined('NOIPCHECK')) {
		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	}
	if (!defined('NOBROWSERNOTIF')) {
		define('NOBROWSERNOTIF', '1');
	}

	// Load Dolibarr environment
	$res = 0;
	// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
	if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
		$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
	}
	// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
	$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
	while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
		$i--; $j--;
	}
	if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
		$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
	}
	if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
		$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
	}
	// Try main.inc.php using relative path
	if (!$res && file_exists("../main.inc.php")) {
		$res = @include "../main.inc.php";
	}
	if (!$res && file_exists("../../main.inc.php")) {
		$res = @include "../../main.inc.php";
	}
	if (!$res && file_exists("../../../main.inc.php")) {
		$res = @include "../../../main.inc.php";
	}
	if (!$res) {
		die("Include of main fails");
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

	try {


		// *** Get response and prepare data
		$returnVars = file_get_contents('php://input');
		$data = json_decode($returnVars);

		// *** Log response 
		$ipn_log_file = 'ipn_helloasso.log';
		$fp = fopen($ipn_log_file, 'a');
		fwrite($fp, " *** Returned from server " . date("d-m-y h:i:s") . " : " . json_encode($data) . " : " . print_r($returnVars) . "\n");
		fclose($fp);

		// *** Decode data
		$paymentdata = new stdclass();
		$paymentdata->invoice_id= $data->metadata->id;
		// $paymentdata->globalamount= $data->amount;
		$paymentdata->globalstate= $data->data->state;
		$paymentdata->socemail= $data->data->payer->email;
		$paymentdata->socid = $data->metadata->socid;
		$paymentdata->eventType = $data->eventType;

		$item= $data->data->items[0];
		$paymentdata->paymentstate= $item->state;
		$paymentdata->orderid= $item->id;
		
		$paymentdata->paymentamount= $data->data->amount;		
		$paymentdata->paymentid= $data->data->id;
		
		//*** Get data from helloasso response */
		require '../lib/HelloAssoApi_Wrapper.php';
		require '../lib/DoliWrapper.php';
	
		//*** Create the payment
		$doliWrapper = new DoliWrapper();
		if ($paymentdata->eventType=== "Payment") {
			$f = fopen('ipn_helloasso.log', 'a+');
            fwrite($f," ***  ipnreturn :  " . date("d-m-y h:i:s") .  json_encode($paymentdata) . "\n");
            fclose($f);

			$data = $doliWrapper->createInvoicePayment($paymentdata);
		}

       } catch (Exception $e) {
            // echo 'Error: ' . date("d-m-y h:i:s") . " - " . $e->getMessage() . "<br>";
            $f = fopen('ipn_helloasso.log', 'a+');
            fwrite($f," *** Error ipnreturn :  " . date("d-m-y h:i:s") ." - ".  $e->getMessage() . " - ". $e->getCode() . " - ".json_encode($paymentdata) . "\n");
            fclose($f);
        }
