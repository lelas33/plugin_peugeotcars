<?php

// Fonctions de connexion à l'API PSA V1
// =====================================
// Il faut 3 informations pour se connecter à cette API
// VIN      : Numero d'identification unique du vehicule (donne sur la carte grise)
// client_id: Client id de l'application créée sur le site PSA
// contract :  Client Secret de l'application créée sur le site PSA


class peugeotcars_api_v1 {

// protected $url_api_im_v1 = 'https://api.groupe-psa.com/connectedcar/v1/dev/';
// protected $client_id = 'dbf9f38e-095a-465e-952c-d212a862c021';
// protected $contract  = 'E8eK2oJ2nO2lA5aC5aW5nB8bT2rW1rO6eY3lU1gM1hF5yG8aR4';

//  protected $url_api_im_v1 = 'https://api-preprod.psa-cloud.com/connectedcar/v1/dev/';
//  protected $client_id = 'bb522166-f952-4a12-8a53-2cfafdb14c25';
//  protected $contract  = 'K2bS4xQ8nH5eJ6rW1nW1nF0vO1nQ1jP3pN5dO7vT8vR7wN4cS1';

  private $url_api_custom_gateway_v1 = 'https://api-custom-gateway.mym.awsmpsa.com/bo/v1/';
  private $url_api_oauth             = 'https://idpcvs.peugeot.com/am/oauth2/';
  private $url_api_oauth2            = 'https://id-dcr.peugeot.com/';

  private function get_headers($fields = null)
  {
		if ( isset($this->token) ) {
			$generique_headers = array(
			  'Content-type: application/json',
			  'Accept: application/json',
				'Authorization: Bearer '.$this->token,
				'Authorization-Provider: '.$this->provider
			);
		}
		else {
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json'
			   );
		}
    if ( isset($fields) ) {
      $custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
    }
    else
    {
      $custom_headers = array();
    }
    return array_merge($generique_headers, $custom_headers);
  }

  private function get_headers2($fields = null)
  {
		if ( isset($this->token) ) {
			$generique_headers = array(
			  'Content-type: application/json',
			  'Accept: application/json',
				'Authorization: Bearer '.$this->token,
				'Authorization-Provider: '.$this->provider
			);
		}
		else {
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			   'Accept-Language: fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3',
			   'Accept-encoding: gzip, deflate, br'
			   );
		}
    if ( isset($fields) ) {
      $custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
    }
    else
    {
      $custom_headers = array();
    }
    return array_merge($generique_headers, $custom_headers);
  }

  private function post_api($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_custom_gateway_v1 . $param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if ( isset($fields) ) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
    }
    $json = curl_exec($session);
    curl_close($session);
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    return json_decode($json);
  }

  private function get_api($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_custom_gateway_v1 . $param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
        echo 'Erreur Curl : ' . curl_error($session);
    }

    // Vérification du code d'état HTTP
    if(!curl_errno($session))  {
     $info = curl_getinfo($session);

     echo 'La requête a mis ' . $info['total_time'] . ' secondes à être envoyée à ' . $info['url'];
    }

    curl_close($session);
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    return json_decode($json);
  }

  private function get_api2($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_oauth . $param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers2($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($session, CURLOPT_REFERER, 'https://mypeugeot.peugeot.fr/');
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
        echo 'Erreur Curl : ' . curl_error($session);
    }

    // Vérification du code d'état HTTP
    if(!curl_errno($session))  {
     $info = curl_getinfo($session);

     echo 'La requête a mis ' . $info['total_time'] . ' secondes à être envoyée à ' . $info['url'];
     echo 'GET_INFO:'.print_r($info, true);
    }
    curl_close($session);
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    echo 'RETURN:'.print_r($json, true);
    return json_decode($json);
    //echo $json;
  }

  private function get_api3($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_oauth2 . $param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers2($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_REFERER, 'https://mypeugeot.peugeot.fr/');
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
    }
    $json = curl_exec($session);

    // Vérifie si une erreur survient
    if(curl_errno($session)) {
        echo 'Erreur Curl : ' . curl_error($session);
    }

    // Vérification du code d'état HTTP
    if(!curl_errno($session))  {
     $info = curl_getinfo($session);

     echo 'La requête a mis ' . $info['total_time'] . ' secondes à être envoyée à ' . $info['url'];
     echo 'GET_INFO:'.print_r($info, true);
    }
    
    curl_close($session);
    //throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    echo 'RETURN LENGTH:'.strlen ($json);
//    for ($id=0; $id<50;$id++)
//      echo 'DT['.$id.']='.$json[$id];
    echo 'RETURN String:'.bin2hex($json);
    return json_decode($json);
//    echo $json;
  }



  private function get_api_($url, $param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $url.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($session, CURLOPT_REFERER, 'https://mypeugeot.peugeot.fr/');
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
    }
    $json = curl_exec($session);
    // Vérifie si une erreur survient
    if(curl_errno($session)) {
      $info = [];
      echo 'Erreur Curl : ' . curl_error($session);
    }
    else {
      $info = curl_getinfo($session);
    }
    curl_close($session);
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    $ret_array["result_njson"] = $json;
    return $ret_array;
  }





  // Get client id (for auth login)
  // ==============================
  // https://api-custom-gateway.mym.awsmpsa.com/bo/v1/ws_parameters/parameters/cvs?brand=AP&country=FR&source=WEB
  function pg_get_client_id()
  {
    $param = "ws_parameters/parameters/cvs?brand=AP&country=FR&source=WEB";
		return $this->get_api($param);
  }

  // Get list of messages for MyPeugeot site
  // =======================================
  // https://api-custom-gateway.mym.awsmpsa.com/bo/v1/folabels?brand=AP&country=FR&language=fr&source=WEB
  function pg_get_appli_msg()
  {
    $param = "folabels?brand=AP&country=FR&language=fr&source=WEB";
		return $this->get_api($param);
  }

// https://api-custom-gateway.mym.awsmpsa.com/bo/v1/favorite_dealer_settings?brand=AP&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/bo/v1/prdv_settings?brand=AP&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/bo/v1/devis_settings?brand=AP&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/car/v1/visual3d?brand=AP&vin=vr3uhzkxzlt075235&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/bo/v1/service_connecte?brand=AP&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/shop/v1/sams_contract?vin=vr3uhzkxzlt075235&brand=AP&country=FR&source=WEB
// https://api-custom-gateway.mym.awsmpsa.com/shop/v1/catalog_connecte?vin=vr3uhzkxzlt075235&brand=AP&country=FR&language=fr&source=WEB

  // Get list of favorite dealers
  // ============================
  // https://api-custom-gateway.mym.awsmpsa.com/bo/v1/favorite_dealer_settings?brand=AP&country=FR&source=WEB
  function pg_get_favorite_dealers()
  {
    $param = "favorite_dealer_settings?brand=AP&country=FR&source=WEB";
		return $this->get_api($param);
  }



  // Login to MyPeugeot Account
  // ==========================
  // https://idpcvs.peugeot.com/am/oauth2/authorize?client_id=551d2ce7-5d17-4a1b-b17f-3a1103f6e635&response_type=code&scope=openid%20profile%20email%20implied_consent&redirect_uri=https://mypeugeot.peugeot.fr&locale=fr-FR
  function pg_login()
  {
    $param = "authorize?client_id=551d2ce7-5d17-4a1b-b17f-3a1103f6e635&response_type=code&scope=openid%20profile%20email%20implied_consent&redirect_uri=https://mypeugeot.peugeot.fr&locale=fr-FR";
		return $this->get_api2($param);
  }

  // Login to MyPeugeot Account
  // ==========================
  // https://id-dcr.peugeot.com/login?openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select&openid.identity=http://specs.openid.net/auth/2.0/identifier_select&openid.mode=checkid_setup&openid.ns=http://specs.openid.net/auth/2.0&newcvs=1&goto=https%3A%2F%2Fidpcvs.peugeot.com%2Fam%2Foauth2%2Fauthorize%3Fclient_id%3D551d2ce7-5d17-4a1b-b17f-3a1103f6e635%26response_type%3Dcode%26scope%3Dopenid%2520profile%2520email%2520implied_consent%26redirect_uri%3Dhttps%253A%252F%252Fmypeugeot.peugeot.fr%26locale%3Dfr-FR&realm=/clientsB2CPeugeot&locale=fr-FR
  function pg_login2()
  {
    $param = "login?openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select&openid.identity=http://specs.openid.net/auth/2.0/identifier_select&openid.mode=checkid_setup&openid.ns=http://specs.openid.net/auth/2.0&newcvs=1&goto=https%3A%2F%2Fidpcvs.peugeot.com%2Fam%2Foauth2%2Fauthorize%3Fclient_id%3D551d2ce7-5d17-4a1b-b17f-3a1103f6e635%26response_type%3Dcode%26scope%3Dopenid%2520profile%2520email%2520implied_consent%26redirect_uri%3Dhttps%253A%252F%252Fmypeugeot.peugeot.fr%26locale%3Dfr-FR&realm=/clientsB2CPeugeot&locale=fr-FR";
		return $this->get_api3($param);
  }


  // Complete Login sequence to MyPeugeot Account
  // ============================================
  // https://id-dcr.peugeot.com/login?openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select&openid.identity=http://specs.openid.net/auth/2.0/identifier_select&openid.mode=checkid_setup&openid.ns=http://specs.openid.net/auth/2.0&newcvs=1&goto=https%3A%2F%2Fidpcvs.peugeot.com%2Fam%2Foauth2%2Fauthorize%3Fclient_id%3D551d2ce7-5d17-4a1b-b17f-3a1103f6e635%26response_type%3Dcode%26scope%3Dopenid%2520profile%2520email%2520implied_consent%26redirect_uri%3Dhttps%253A%252F%252Fmypeugeot.peugeot.fr%26locale%3Dfr-FR&realm=/clientsB2CPeugeot&locale=fr-FR
  function pg_full_login()
  {
    // Step1 : get_client_id
    print ("STEP1\n");
    $url   = "https://api-custom-gateway.mym.awsmpsa.com/bo/v1/";
    $param = "ws_parameters/parameters/cvs?brand=AP&country=FR&source=WEB";
		$ret= $this->get_api_($url, $param);
    //var_dump($ret["result"]);
    $login_url = $ret["result"]->data->cvs->url; 
    $client_id = $ret["result"]->data->cvs->clientid;
    print ("login_url:".$login_url."\n");
    print ("client_id:".$client_id."\n");
    
    // Step 2: login pour Autorisation
    // https://idpcvs.peugeot.com/am/oauth2/authorize?client_id=551d2ce7-5d17-4a1b-b17f-3a1103f6e635&response_type=code&scope=openid%20profile%20email%20implied_consent&redirect_uri=https://mypeugeot.peugeot.fr&locale=fr-FR
    print ("STEP2\n");
    $url   = $login_url.'/';
    $param = "authorize?client_id=".$client_id."&response_type=code&scope=openid%20profile%20email%20implied_consent&redirect_uri=https://mypeugeot.peugeot.fr&locale=fr-FR";
		$ret= $this->get_api_($url, $param);
    //var_dump($ret["info"]);
    $http_code    = $ret["info"]["http_code"];
    $redirect_url = $ret["info"]["redirect_url"];
    print ("http_code:".$http_code."\n");
    print ("redirect_url:".$redirect_url."\n");
    if (($http_code != 301) || ($redirect_url == "")) {
      // redirection attendue
      print ("Erreur : redirection attendue\n");
      return;      
    }

    // Step 3: login pour Autorisation => Redirection
    print ("STEP3\n");
    $url   = $redirect_url;
		$ret= $this->get_api_($url, "");
    var_dump($ret["info"]);
    var_dump($ret["result_njson"]);


  }


}
?>