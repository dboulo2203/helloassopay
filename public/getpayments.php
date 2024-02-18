<?php

/**
 * This file is called by helloasso when then user has ended its transaction
 * This function display a message to the user : success message or failure message.  
 */

	// *** pervent from Dolibarr login check
	if (!defined('NOLOGIN'))		define("NOLOGIN", 1); // This means this output page does not require to be logged.
	if (!defined('NOCSRFCHECK'))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

	// Load Dolibarr environment
	$res = 0;
	// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
	if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
	// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
	$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
	while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
	if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
	if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
	// Try main.inc.php using relative path
	if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
	if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
	if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
	if (!$res) die("Include of main fails");


    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

	global $conf;
    global $langs;
    global $db;

    // Translations
	// $langs->loadLangs(array("admin", "helloassopay@helloassopay"));

    require '../lib/HelloAssoApi_Wrapper.php';
		require '../lib/DoliWrapper.php';

    try {
        /*** Log start */
		 print("**** *** Start getPayments rebuild " . date("d-m-y h:i:s")."</br>");

   		// *** Get the payment url from Helloasso
		$helloassoApiWrapper = new HelloAssoApiWrapper();

		$helloassoApiWrapper->initToken();
		$helloassoApiWrapper->get_access_token();

        /*** Build table */
        $return = buildGetPayments();
        
        /*** Log end*/
 		print("**** *** End getPayments rebuild " . date("d-m-y h:i:s")."</br>");
		print "*** ". date("d-m-y h:i:s") ." - Process Update base reportOrders Ok . " . $return . " lines</br>";

	} catch(Exception  $exp) {
       //  $f = fopen('analysedordersbuild.log', 'a+');
       //  fwrite($f,"  *** Error analysedorders rebuild " . date("d-m-y h:i:s") . " : " . $exp->getMessage(). "\n");
       //  fclose($f);
		//  print();
		dol_syslog("  *** Error getHelloAsso payments  " . date("d-m-y h:i:s") . " : " . $exp->getMessage()."</br>" ,LOG_ERR);

		}

    exit();  

    /**
	 * Main function of the Helloasso payment get
	 * @throws Exception 
	 */
	 function buildGetPayments(  ) {
		
		ini_set('max_execution_time', '300');
		
		global $db, $conf;

		/*** Save in the database */
		try{
  			// *** Drop table table if exists
			$db->begin();
			$sql = "DROP TABLE IF EXISTS rep_helloassopayments";
			if (!$db->query($sql)) 
				throw new Exception("impossible drop table :" .$db->lasterror()); 
			$db->commit();

			// *** Create table if exists
			$db->begin();
			$sql = "CREATE TABLE rep_helloassopayments " 
			. "(ord_id CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin "
			. " ,ord_date DATETIME"
			. ", ord_formSlug CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin "
			. ", ord_formType CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", ord_organizationName CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"			
			. ", ord_organizationSlug CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", ord_formName CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", order_isAnonymous INT DEFAULT 0"
			. ", order_isAmountHidden INT DEFAULT 0"			
            . ", payer_email CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
            . ", payer_country CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
            . ", payer_dateOfBirth DATETIME"
            . ", payer_firstName CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
            . ", payer_lastName CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_cashOutState CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_paymentReceiptUrl CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_id CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_amount INT DEFAULT 0"
			. ", pay_date DATETIME"   
			. ", pay_paymentMeans CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_installmentNumber CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", pay_state CHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
			. ", payment_createdAt DATETIME"
			. ", payment_updatedAt DATETIME"
			. ") ";			
 			if (!$db->query($sql)) 
				throw new Exception("impossible CREATE TABLE : " .$db->lasterror() ." - " . $sql); 
			$db->commit();

            // *** Iterate the order database, analyse and save the orders 
	 		$resultlineNb = buildGetPaymentsTable( $limit = 100,   $sqlfilters = '');


	} catch(Exception  $exp) {
		$db->rollback();
		throw new Exception('Erreur rep_helloassopayments  : ' . $exp->getMessage());
	}

    return "Table rep_helloassopayments successfully built : " . $resultlineNb . " lines";;
	}




     /**
	 * Get the payments, iterate across the pages and save the payments in the database
	 *
	 * @url post buildReportOrdersTable/
	 * 
	 * @param int		       $limit		        Limit for list
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  int				number of lines                                
	 */
	function buildGetPaymentsTable(  $limit = 100,   $sqlfilters = '')
	{
		global $db, $conf;
		$totatLines=0;
        // *** Call the Oauth2.0 fucntion, to get the access token
		$helloassoApiWrapper = new HelloAssoApiWrapper();
		$helloassoApiWrapper->initToken();
		$helloassoApiWrapper->get_access_token();

		// *** Get the nomber of pages 
		$numberOfPages= $helloassoApiWrapper->getPaymentsPageNumber();

       
		/*** Fill  table */
		// *** Iterate the number of pages
		for ($pageIndex=1;$pageIndex<=$numberOfPages; ++$pageIndex) { 
			// *** Get the data from the HElloasso API
			$payments = $helloassoApiWrapper->getPayments($pageIndex);
			$paymentsList=$payments["data"];

			if (is_array($paymentsList)) {
				// *** For each Payment, Create a record in the database 

				for ($i=0;$i<count($paymentsList);++$i) {
					$payment = $paymentsList[$i];
					$db->begin();
						$sql = "INSERT INTO rep_helloassopayments " 
							."  VALUES ("
						. "'". $db->escape($payment["order"]["id"]) . "'" 
						.  "," ."'". $db->escape($payment["order"]["date"] ). "'"
						. "," . "'". $db->escape($payment["order"]["formSlug"] ). "'"
						. "," . "'". $db->escape($payment["order"]["formType"]) . "'"		
						. "," . "'". $db->escape($payment["order"]["organizationName"]) . "'"
						. "," . "'". $db->escape($payment["order"]["organizationSlug"]) . "'"
						. "," . "'". $db->escape($payment["order"]["formName"]) . "'"
						. "," . "'". $db->escape($payment["order"]["isAnonymous"]) . "'"
						. "," . "'". $db->escape($payment["order"]["isAmountHidden"]) . "'"					
						. "," . "'". $db->escape($payment["payer"]["email"]) . "'"
						. "," . "'". $db->escape($payment["payer"]["country"]) . "'"
						. "," . "'". $db->escape($payment["payer"]["dateOfBirth"]) . "'"
						. "," . "'". $db->escape($payment["payer"]["firstName"]) . "'"
						. "," . "'". $db->escape($payment["payer"]["lastName"]) . "'"
						. "," . "'". $db->escape($payment["cashOutState"]) . "'"
						. "," . "'". $db->escape($payment["paymentReceiptUrl"]) . "'"
						. "," . "'". $db->escape($payment["id"]) . "'"
						. "," . "'". $db->escape($payment["amount"]) . "'"
						. "," . "'". $db->escape($payment["date"]) . "'"
						. "," . "'". $db->escape($payment["paymentMeans"]) . "'"
						. "," . "'". $db->escape($payment["installmentNumber"]) . "'"
						. "," . "'". $db->escape($payment["state"]) . "'"
						. "," . "'". $db->escape($payment["meta"]["createdAt"]) . "'"
						. "," . "'". $db->escape($payment["meta"]["updatedAt"]) . "'"
						.");";
						
						if (!$db->query($sql)) 
							throw new Exception("impossible INSERT INTO : " .$db->lasterror() . " - " .$sql); 
						
						$db->commit();	
				}
				$totatLines+=$i;
			}
        }
		return $totatLines;
    }
