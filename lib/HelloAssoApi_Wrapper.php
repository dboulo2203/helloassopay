<?php


/**
 * This class could appear complicated but once prefill documentation in your hands it will make perfect sens !
 * TODO : add token refresh with expiration date and time
 * TODO ; voi r gestion des erreurs et de leur identification
 * 
 */
class HelloAssoApiWrapper
{

    public $access_token;
    private $refresh_token;
    public $name;
    public $organizationSlug;

    public function __construct()
    {
       //  $this->initToken();
    }

    /**
     * Init helloasso API, get the access token 
     */
    public function initToken()
    {
        global $langs, $conf;
       //  try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $conf->global->HELLOASSOPAY_BASEURL . "/oauth2/token",
                // CURLOPT_URL => 'https://api.helloasso-sandbox.com/oauth2/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                // CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id=' . $conf->global->HELLOASSOPAY_CLIENT_ID . '&client_secret=' . $conf->global->HELLOASSOPAY_CLIENT_SECRET,
                // CURLOPT_POSTFIELDS => "'" . $access_string . "'", 
                
                // CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id=06c69c161781420aa52aa2ac47297c4e&client_secret=3oWky6O8RdEUUx%2Fq%2BYJ%2BOraXBc6QdzpH',
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id='.urlencode($conf->global->HELLOASSOPAY_CLIENT_ID).'&client_secret='.urlencode($conf->global->HELLOASSOPAY_CLIENT_SECRET).'',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            // *** Check curl  error
            $err = curl_error($curl);
            if ($err)
                throw new Exception("API Error errno: " . $err, curl_errno($curl));

           if (empty($response))
                throw new Exception("API Error : initToken empty response from API" , 600);

                // *** Get Data from the APii response
            $decodedResponse = json_decode($response, true);

            if (array_key_exists("error", $decodedResponse))
                throw new Exception("API Error : " . $decodedResponse->error . $decodedResponse->error_description, 600);

            $this->access_token = $decodedResponse["access_token"];
            $this->refresh_token = $decodedResponse["refresh_token"];
        // throw new Exception( $this->access_token,600);

            curl_close($curl);
       //  } catch (Exception $e) {
       //     throw new Exception($exp->getMessage(),$exp->getCode());

        //}
    }

    /**
     * Return the access token
     */
    function get_access_token()
    {
        return $this->access_token;
    }

    /**
     * Get organization details
     */
    public function getorg()
    {
        $curl = curl_init();

        $params = array(
            CURLOPT_URL =>  $conf->global->HELLOASSOPAY_BASEURL . '/v5/users/me/organizations',            // ."&redirect_uri=". CALLBACK_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPHEADER => array(
                "Authorization:  Bearer " . $this->access_token
            ),
            // CURLOPT_STDERR => $f,
        );

        curl_setopt_array($curl, $params);

       //  try {
            $response = curl_exec($curl);
 
            if (empty($response))
                throw new Exception("API Error : get Org empty response" , 600);
            
            // *** Check curl  error
            $err = curl_error($curl);
            if ($err)
                throw new Exception("API Error : " . $err, 600);
 
            // *** Get Data from the APii response
            $decodedResponse = json_decode($response, true);

            // *** Check server error
            if (array_key_exists("error", $decodedResponse))
                throw new Exception("API Error : " . $decodedResponse->error . $decodedResponse->error_description, 600);

            // *** prepare data     
            $this->name = $decodedResponse["name"];
            $this->organizationSlug = $decodedResponse["organizationSlug"];

            // *** Close
            curl_close($curl);

        // } catch (Exception $exp) {
        //     throw new Exception($exp->getMessage(),$exp->getCode());
        // }

        return $response;
    }
    /**
     * Call HelloAsso API to initialize checkout
     * If ok this function return raw response
     * Else an error code
     */
    public function initCart($data,$tracemode)
    {
        global $langs, $conf;
        $invoice = $data[0];
        $thirdparty = $data[1];

        $curl = curl_init();
 
       
        // *** Split the Dolibarr name into firstname and lastname
        $lastnamepart = substr($thirdparty->name, 0, strpos($thirdparty->name , " "));
        $namepart2 = substr($thirdparty->name,strpos($thirdparty->name , " ")+1,strlen($thirdparty->name) );        
        if (strpos($namepart2 , " ") ==false)
            $firstnamepart = $namepart2;           
        else
            $firstnamepart = substr($namepart2,0, strpos($namepart2 , " ") );

        // *** If required trace the string that will be sent to the helloasso api
       if (!empty($tracemode)) {
        $apiString = json_encode(array(
             // CURLOPT_URL => 'https://api.helloasso-sandbox.com/v5/organizations/dhagpo-kundreul-ling-espace-de-tests/checkout-intents',
            // CURLOPT_URL => $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/'.$conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/Dhagpo Kundreul Ling - espace de tests/checkout-intents',           
            CURLOPT_URL => $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/'.$conf->global->HELLOASSOPAY_ORGANISM_SLUR.'/checkout-intents',           
           // CURLOPT_URL => $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/Dhagpo Kundreul Ling - espace de tests/checkout-intents',           
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization:  Bearer " . $this->access_token
            ),
            CURLOPT_POSTFIELDS => '{
               "totalAmount": "' . number_format(floatval($invoice->remaintopay_calculated) * 100, 0, ".", '') . '",
                "initialAmount": "' . number_format(floatval($invoice->remaintopay_calculated) * 100, 0, ".", '') . '",
                "itemName": "'. $conf->global->HELLOASSO_LIBELLE_DESIGNATION. '",
                "backUrl" : "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=errorurl&ref=' . $invoice->id . '" ,
                "errorUrl" :  "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=errorurl&ref=' . $invoice->id . '",
                "returnUrl":  "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=returnurl&ref=' . $invoice->id . '",
                "containsDonation": false,               
                "payer": {
                   "firstName": "' . $firstnamepart . '",
                    "lastName": "' . $lastnamepart . '",
                    "email": "' . $thirdparty->email . '"
                },
                "metadata": {
                   "id": "' . $invoice->id . '",
                    "socid":"' . $invoice->socid . '",
                    "userId": 98765
                }
            }'
        ),JSON_UNESCAPED_SLASHES);

        // *** Trace mode
        $f = fopen('return_helloasso.log', 'a+');
        fwrite($f," *** Trace mode :  2 - Init card API String : " . date("d-m-y h:i:s") .  $apiString . "\n");
         fclose($f);
		}

        curl_setopt_array($curl, array(
            // CURLOPT_URL => 'https://api.helloasso-sandbox.com/v5/organizations/Dhagpo-test/checkout-intents',
           CURLOPT_URL => $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/'.$conf->global->HELLOASSOPAY_ORGANISM_SLUR.'/checkout-intents',           
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization:  Bearer " . $this->access_token
            ),
            CURLOPT_POSTFIELDS => '{
               "totalAmount": "' . number_format(floatval($invoice->remaintopay_calculated) * 100, 0, ".", '') . '",
                "initialAmount": "' . number_format(floatval($invoice->remaintopay_calculated) * 100, 0, ".", '') . '",
                "itemName": "Paiement de la facture Dhagpo ",
                "backUrl" : "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=errorurl&ref=' . $invoice->id . '" ,
                "errorUrl" :  "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=errorurl&ref=' . $invoice->id . '",
                "returnUrl":  "' . DOL_MAIN_URL_ROOT . '/custom/helloassopay/public/backurl.php?action=returnurl&ref=' . $invoice->id . '",
                "containsDonation": false,               
                "payer": {
                   "firstName": "' . $firstnamepart . '",
                    "lastName": "' . $lastnamepart . '",
                    "email": "' . $thirdparty->email . '"
                },
                "metadata": {
                   "id": "' . $invoice->id . '",
                    "socid":"' . $invoice->socid . '",
                    "userId": 98765
                }
            }'
        ));

      //  try {
            $response = curl_exec($curl);

            // *** Trace mode
            if (!empty($tracemode)) {
                $f = fopen('return_helloasso.log', 'a+');
                fwrite($f," *** Trace mode 2b " . date("d-m-y h:i:s") ." get checkout ling: - : " .$response . "\n");
                fclose($f);
            }
            // *** Check curl  error
            $err = curl_error($curl);
            if ($err)
                throw new Exception("API Error : " . $err, 600);
            
           if (empty($response))
                throw new Exception("Erreur de connexion à HelloAsso (retour de helloAssos vide)" , 600);

            // *** Get Data from the APi response
            $decodedResponse = json_decode($response, true);

            // *** Check server error
            if (array_key_exists("error", $decodedResponse))
                throw new Exception(" Erreur de connexion à HelloAsso (error) . " . $decodedResponse->error . $decodedResponse->error_description, 600);
           if (array_key_exists("errors", $decodedResponse))
                throw new Exception(" Erreur de connexion à HelloAsso (errors) : " . $response, 600);   
            if (array_key_exists("message", $decodedResponse))
                throw new Exception(" Erreur de connexion à HelloAsso (message) " . $decodedResponse["message"], 600);

            $redirectUrl = $decodedResponse["redirectUrl"];
            $id = $decodedResponse["14061"];

            curl_close($curl);
       //  } catch (Exception $exp) {
       //      throw new Exception($exp->getMessage(),$exp->getCode());
       // }
        return $decodedResponse;
    }

    /**
     * Get organization details
     */
    public function getPayments($pageIndex=1)
    {
         global $langs, $conf;
        $curl = curl_init();
        
        $params = array(
            CURLOPT_URL =>  $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/'.$conf->global->HELLOASSOPAY_ORGANISM_SLUR.'/payments?pageSize=20&pageIndex='. $pageIndex,            // ."&redirect_uri=". CALLBACK_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization:  Bearer " . $this->access_token
            ),
            // CURLOPT_STDERR => $f,
        );

        curl_setopt_array($curl, $params);
       //  try {
            
            $response = curl_exec($curl);
            // print($response);
            
            if (empty($response))
                throw new Exception("API Error : get payments empty response" , 600);
            
            // *** Check curl  error
            $err = curl_error($curl);
            if ($err)
                throw new Exception("API Error : " . $err, 600);
 
            // *** Get Data from the APii response
            $decodedResponse = json_decode($response, true);

            // *** Check server error
            if (array_key_exists("error", $decodedResponse))
                throw new Exception("API Error : " . $decodedResponse->error . $decodedResponse->error_description, 600);

            // *** prepare data     
            $this->name = $decodedResponse["name"];
            $this->organizationSlug = $decodedResponse["organizationSlug"];

            // *** Close
            curl_close($curl);

        return $decodedResponse;
    }


      /**
     * Get organization details
     */
    public function getPaymentsPageNumber()
    {
         global $langs, $conf;
        $curl = curl_init();
        
        $params = array(
            CURLOPT_URL =>  $conf->global->HELLOASSOPAY_BASEURL . '/v5/organizations/'.$conf->global->HELLOASSOPAY_ORGANISM_SLUR.'/payments?pageSize=20',            // ."&redirect_uri=". CALLBACK_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization:  Bearer " . $this->access_token
            ),
            // CURLOPT_STDERR => $f,
        );

        curl_setopt_array($curl, $params);
       //  try {
            
            $response = curl_exec($curl);
            // print($response);
            
            if (empty($response))
                throw new Exception("API Error : get payments empty response" , 600);
            
            // *** Check curl  error
            $err = curl_error($curl);
            if ($err)
                throw new Exception("API Error : " . $err, 600);
 
            // *** Get Data from the APii response
            $decodedResponse = json_decode($response, true);

            $pagenumber= $decodedResponse["pagination"]["totalPages"];

            // *** Check server error
            if (array_key_exists("error", $decodedResponse))
                throw new Exception("API Error : " . $decodedResponse->error . $decodedResponse->error_description, 600);

            // *** prepare data     
           //  $this->name = $decodedResponse["name"];
           //  $this->organizationSlug = $decodedResponse["organizationSlug"];

            // *** Close
            curl_close($curl);

        return $pagenumber;
    }
}


/********************************************************************************************************************* */
/*      
/**
 * Split amount into terms and set terms date to first day of the month
 */
    /*    private function manageMultiplePayment($paymentCount, $totalAmount, $body)
    {
        $termsAmount = round($totalAmount / $paymentCount, 2, PHP_ROUND_HALF_DOWN);
        $rest = round($totalAmount - ($termsAmount * $paymentCount), 2);

        $body['initialAmount'] = $termsAmount;

        $body['terms'] = array();
        $today = getdate();
        $nextPayment = new \DateTime($today['year'] . '-' . $today['month'] . '-01');

        for ($i = 1; $i < $paymentCount; $i++) {
            $nextPayment->add(new \DateInterval('P1M'));
            array_push($body['terms'], array(
                'amount' => $i == $paymentCount - 1 ? ($termsAmount + $rest) : $termsAmount,
                'date' => $nextPayment->format('Y-m-d')
            ));
        }

        return $body;
    } www.kusala.fr/dolibarr/custom/helloassopay/public/start.php?ref=126*/
