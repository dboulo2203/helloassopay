<?php
/*  ****** TOD LIST
* TODO : add token refresh with expiration date and time
 * 
 * Contrainte : il n'y a qu'un paiement par transaction
 ******************************************************************/
 /* @
 * @param : ref : the id of the invoice to be paid
 * @return : redirect the useer to the payment link sent by helloasso
 */

	// *** Dolibarr parameters : Dolibarr must not check if the user is logged
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


	// *** Load Dolibarr environment
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
		global $langs, $conf;
		if (empty($conf->global->HELLOASSOPAY_BASEURL))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_BASEURL");
		if (empty($conf->global->HELLOASSOPAY_BASEURL))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_BASEURL");
		if (empty($conf->global->HELLOASSOPAY_CLIENT_ID))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_CLIENT_ID");
		if (empty($conf->global->HELLOASSOPAY_CLIENT_SECRET))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_CLIENT_SECRET");
		if (empty($conf->global->HELLOASSOPAY_CLIENT_SECRET))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_CLIENT_SECRET");
		if (empty($conf->global->HELLOASSOPAY_ORGANISM_SLUR))
			throw new Exception("Erreur de Configuration du module :  HELLOASSOPAY_ORGANISM_SLUR");
		if (empty($conf->global->HELLOASSO_PAYMENTMODE))
			throw new Exception("Erreur de Configuration du module :  HELLOASSO_PAYMENTMODE");
		if (empty($conf->global->HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS))
			throw new Exception("Erreur de Configuration du module :  HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS");
		if (empty($conf->global->HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE))
			throw new Exception("Erreur de Configuration du module :  HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE");

		// *** Get the invoice id
		$tag = $_GET["ref"];
		if (empty($tag))
			throw new Exception("L'id de la facture est obligatoire (GET[ref]) ",600);

		$tracemode = $_GET["tracemode"];
	
			// echo " Invoice ID : " . $tag . "<br>";
		require '../lib/HelloAssoApi_Wrapper.php';
		require '../lib/DoliWrapper.php';

		// *** Get the invoice and the thirdparty
		$doliWrapper = new DoliWrapper();
		$data = $doliWrapper->getInvoiceDetails($tag);

		// *** Get the payment url from Helloasso
		$helloassoApiWrapper = new HelloAssoApiWrapper();

		$helloassoApiWrapper->initToken();
		$helloassoApiWrapper->get_access_token();
		print($helloassoApiWrapper->access_token);
	
		if ($tracemode===true) {
			$f = fopen('return_helloasso.log', 'a+');
            fwrite($f,"  *** Trace mode 1 " . date("d-m-y h:i:s") ." - access token : " . json_encode($helloassoApiWrapper->access_token) . "\n");
            // fwrite($f," *** Trace mode :  3 : " . date("d-m-y h:i:s") .  json_encode($redirecturl) . "\n");
            fclose($f);
		 }
		$redirecturl = $helloassoApiWrapper->initCart($data,$tracemode );
		if (!empty($tracemode)) {
			$f = fopen('return_helloasso.log', 'a+');
            fwrite($f," *** Trace mode 3 " . date("d-m-y h:i:s") ." : - redirect url : " . date("d-m-y h:i:s") .  json_encode($redirecturl) . "\n");
            fclose($f);
		}

		// *** redirect to helllloasso site
		$r = "Location: " . $redirecturl["redirectUrl"];
		header($r);

	} catch (Exception $e) {
		// *** Display message  
		print "<link rel='stylesheet' href='./main.css' type='text/css'>";
		print("<div class='helloassopage'>");
		if (!empty($conf->global->HELLOASSO_HEADER_AFTER_PAYMENT))
			print '<div class=\'helloassoheader\'>'. $conf->global->HELLOASSO_HEADER_AFTER_PAYMENT.'</div>';
		
		print '<div class=\'helloassomessage\'>'  . $e->getMessage() ;
		print "<p> Ce message apparait car le site a rencontré un problème. Veuillez contacter l'administrateur</p>";
		if (!empty($conf->global->HELLOASSO_URL_AFTER_PAYMENT))
			print  '<div class=\'helloassobutton\'><a href="' .$conf->global->HELLOASSO_URL_AFTER_PAYMENT. '"><button class=\'hellobutton\'>Cliquez ici pour revenir sur le site de réservations</button></a></div>';
		print "</div>";
		
			// *** Log the error
		// if (!empty($tracemode)) {
			$f = fopen('return_helloasso.log', 'a+');
			fwrite($f," *** Trace Error " . date("d-m-y h:i:s") .  " - " . $e->getMessage()  . "\n");
			fclose($f);
		// }

		Die();
	}

exit();
