<?php

// Fonctions de connexion aux API PSA
// ==================================
// Il faut 3 informations pour se connecter à cette API
// VIN      : Numero d'identification unique du vehicule (donne sur la carte grise)
// client_id: Client id de l'application créée sur le site PSA
// contract :  Client Secret de l'application créée sur le site PSA

// Type d'encodage des donnees envoyees
define ('ENC_NO',   0);
define ('ENC_JSON', 1);
define ('ENC_JSON2',2);
// Type de decodage des donnees recues
define ('DEC_NO',   0);
define ('DEC_JSON', 1);



class peugeotcars_api_v1 {

  // Constantes pour la classe
  protected $url_api_custom_gateway_v1 = 'https://api-custom-gateway.mym.awsmpsa.com/bo/v1/';
  protected $url_api_oauth             = 'https://idpcvs.peugeot.com/am/oauth2/';
  protected $url_api_oauth2            = 'https://id-dcr.peugeot.com/';
  protected $url_api_me                = 'https://api-me.mym.awsmpsa.com/v1/';
  protected $url_api_car               = 'https://api-car.mym.awsmpsa.com/v1/';
  protected $url_api_sw                = 'https://api.groupe-psa.com/applications/majesticf/v1/';
	protected $username;
	protected $password;
	protected $api_me_token;



  // ==============================
  // General function : login
  // ==============================
  function login($token)
	{
    $this->api_me_token = $token;
	}

  // ==============================
  // Functions dedicated to api_car
  // ==============================
  // headers definition
  private function get_headers_api_car($fields = null)
  {
    $generique_headers = array(
       'Content-type: application/json',
       'Accept: application/json'
       );
    if (isset($fields)) {
      $custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
    }
    else {
      $custom_headers = array();
    }
    return array_merge($generique_headers, $custom_headers);
  }

  // GET HTTP command
  private function get_api_car($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_car.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_api_car($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
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
    return $ret_array;
  }

  // ===============================================
  // Functions dedicated to api_me, with token usage
  // ===============================================
  // headers definition
  private function get_headers_api_me($fields = null)
  {
    $generique_headers = array(
       'Content-type: application/json',
       'Accept: application/json',
       'token: '.$this->api_me_token
       );
    if ( isset($fields) ) {
      $custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
    }
    else {
      $custom_headers = array();
    }
    return array_merge($generique_headers, $custom_headers);
  }

  // GET HTTP command
  private function get_api_me($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_me.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_api_me($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode ($fields));
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
    return $ret_array;
  }

  // =============================
  // Functions dedicated to api_sw
  // =============================
  // headers definition
  private function get_headers_api_sw($fields = null)
  {
    $generique_headers = array(
       'Content-type: application/json',
       'Accept: application/json, text/plain, */*',
       );
    if ( isset($fields) ) {
      $custom_headers = array('Content-Length: '.strlen($fields));
    }
    else {
      $custom_headers = array();
    }
    return array_merge($generique_headers, $custom_headers);
  }

  // GET HTTP command
  private function get_api_sw($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_sw.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_api_sw($fields));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if (isset($fields)) {
      curl_setopt($session, CURLOPT_POSTFIELDS, $fields);
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
    return $ret_array;
  }
  
  // POST HTTP command
  private function post_api_sw($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_sw.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers_api_sw($fields));
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if ( isset($fields) ) {
      curl_setopt($session, CURLOPT_POSTFIELDS, $fields);
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
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }


  // ==========================================
  // Information disponibles par l'API:api.car
  // ==========================================
  // Information sur le véhicule
  // Renvoi le code LCDV, le label (nom voiture), la date de début de garantie, la photo(lien)
  function pg_api_car_vehicule($vin)
  {
    $param = "vehicle?brand=AP&vin=".$vin."&country=FR&language=fr&source=APP";
		$ret = $this->get_api_car($param);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    // Liste des champs utiles dans le retour de l'API
    $retf["short_label"] = $ret["result"]->short_label;
    $retf["lcdv"] = $ret["result"]->lcdv;
    $retf["warranty_start_date"] = $ret["result"]->warranty_start_date;
    $retf["visual"] = $ret["result"]->visual;
    $retf["eligibility"] = $ret["result"]->eligibility;
    $retf["types"] = $ret["result"]->types;
    return $retf;
  }

  // Information sur le logiciel RCC
  // Renvoi la version disponible du logiciel RCC, et les liens de téléchargement
  function pg_api_car_rcc_sw($vin)
  {
    $param = "vehicle/rcc/soft?brand=AP&country=FR&vin=".$vin."&source=WEB";
		$ret = $this->get_api_car($param);
    var_dump($ret["info"]);
    var_dump($ret["result"]);
  }

  // Information sur le logiciel NAC (SW et Carto)
  // Renvoi la version disponible du logiciel NAC+Base carto et les liens de téléchargement
  function pg_api_car_nac_sw($vin)
  {
    $param = "vehicle/nac/soft?brand=AP&country=FR&vin=".$vin."&source=WEB";
		$ret = $this->get_api_car($param);
    var_dump($ret["info"]);
    var_dump($ret["result"]);
    $param = "vehicle/nac/carto?brand=AP&country=FR&vin=".$vin."&source=WEB";
		$ret = $this->get_api_car($param);
    var_dump($ret["info"]);
    var_dump($ret["result"]);
  }
  

  // ==========================================
  // Information disponibles par l'API:api.me
  // ==========================================
  // Brand ID: Customer data => Nécessite un code CVS
  // Get detailed customer information for the given cvs code
  // Renvoi le token pour l'accès aux autres fonctions de l'API
  // [Full request URI: https://api-me.mym.awsmpsa.com/v1/brandid/customer_data?brand=AP&country=FR&language=fr&code=cc8642f8-0994-4243-a548-080dd1dd1774&redirect_uri=https://mypeugeot.peugeot.fr&source=WEB]
  function pg_api_me_customer_data()
  {
//    $param = "/v1/brandid/customer_data?source=WEB&brand=AP&country=FR&language=fr";
		$ret = $this->get_api_me($param);
    var_dump($ret["info"]);
    var_dump($ret["result"]);
  }

  // User Vehicule: Vehicules => Interessant
  // Renvoi pour la création du véhicule : Label + lien sur photo + date début garantie
  // Renvoi pour le suivi du véhicule : Kilométrage + date
  function pg_api_me_vehicules($vin)
  {
    $param = "user/vehicles?vin=".$vin."&brand=AP&country=FR&language=fr&source=WEB";
		$ret = $this->get_api_me($param);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    if (isset($ret["result"]->success)) {
      $retf["sucess"]     = "OK";
      $retf["mileage_km"] = $ret["result"]->success->mileage->value;
      $retf["mileage_ts"] = $ret["result"]->success->mileage->timestamp;
    }
    else {
      $retf["sucess"]     = "KO";
    }
    return($retf);
  }

  // User Vehicule: Vehicules maintenance => Interessant
  // Revoi pour le suivi du véhicule : opération de maintenance à planifier
  function pg_api_me_vehicules_maintenance($vin)
  {
    $param = "user/vehicles/maintenances?vin=".$vin."&brand=AP&country=FR&language=fr&source=WEB";
		$ret = $this->get_api_me($param);
    // var_dump($ret["info"]);
    // var_dump($ret["result"]);
    if (isset($ret["result"]->success)) {
      $retf["sucess"]     = "OK";
      $retf["mileage_km"] = $ret["result"]->success->mileage;
      // Premiere visite
      $retf["visite1_ts"]      = $ret["result"]->success->pas[1]->theo->timestamp;
      $retf["visite1_mileage"] = $ret["result"]->success->pas[1]->theo->mileage;
      $retf["visite1_age"]     = $ret["result"]->success->pas[1]->theo->age;
      if (isset($ret["result"]->success->pas[1]->labels)) {
        for ($i=0; $i<count($ret["result"]->success->pas[1]->labels); $i++) {
          $retf["visite1_lb_title"][$i]= $ret["result"]->success->pas[1]->labels[$i]->title;
          $retf["visite1_lb_body"][$i]= [];
          if (isset($ret["result"]->success->pas[1]->labels[$i]->body)) {
            for ($j=0; $j<count($ret["result"]->success->pas[1]->labels[$i]->body); $j++) {
              $retf["visite1_lb_body"][$i][$j] = $ret["result"]->success->pas[1]->labels[$i]->body[$j];
            }
          }
        }
      }
      // Seconde visite
      $retf["visite2_ts"]      = $ret["result"]->success->pas[2]->theo->timestamp;
      $retf["visite2_mileage"] = $ret["result"]->success->pas[2]->theo->mileage;
      $retf["visite2_age"]     = $ret["result"]->success->pas[2]->theo->age;
      if (isset($ret["result"]->success->pas[2]->labels)) {
        for ($i=0; $i<count($ret["result"]->success->pas[2]->labels); $i++) {
          $retf["visite2_lb_title"][$i]= $ret["result"]->success->pas[2]->labels[$i]->title;
          $retf["visite2_lb_body"][$i]= [];
          if (isset($ret["result"]->success->pas[2]->labels[$i]->body)) {
            for ($j=0; $j<count($ret["result"]->success->pas[2]->labels[$i]->body); $j++) {
              $retf["visite2_lb_body"][$i][$j] = $ret["result"]->success->pas[2]->labels[$i]->body[$j];
            }
          }
        }
      }
      // Troisieme visite
      $retf["visite3_ts"]      = $ret["result"]->success->pas[3]->theo->timestamp;
      $retf["visite3_mileage"] = $ret["result"]->success->pas[3]->theo->mileage;
      $retf["visite3_age"]     = $ret["result"]->success->pas[3]->theo->age;
      if (isset($ret["result"]->success->pas[3]->labels)) {
        for ($i=0; $i<count($ret["result"]->success->pas[3]->labels); $i++) {
          $retf["visite3_lb_title"][$i]= $ret["result"]->success->pas[3]->labels[$i]->title;
          $retf["visite3_lb_body"][$i]= [];
          if (isset($ret["result"]->success->pas[3]->labels[$i]->body)) {
            for ($j=0; $j<count($ret["result"]->success->pas[3]->labels[$i]->body); $j++) {
              $retf["visite3_lb_body"][$i][$j] = $ret["result"]->success->pas[3]->labels[$i]->body[$j];
            }
          }
        }
      }
    }
    else {
      $retf["sucess"]     = "KO";
    }
    return($retf);
  }


  // ==========================================
  // Information disponibles par l'API:api.sw
  // ==========================================
  // Recherche de mise à jour logicielles => Interessant
  // Renvoie la version courante des SW (par exemple RCC), et la derniere version disponible + date de cette version + lien de téléchargement
  // Logiciels existants: ("rcc-firmware", "ovip-int-firmware-version", "map-eur")
  function pg_api_sw_updates($vin, $sw)
  {
    $param = "getAvailableUpdate?client_id=1eeecd7f-6c2b-486a-b59c-8e08fca81f54";
    $fields = '{"vin":"'.$vin.'","softwareTypes":[{"softwareType":"'.$sw.'"}]}';
		$ret = $this->post_api_sw($param, $fields);
    $retf["sw_type"]           = $ret["result"]->software[0]->softwareType;
    $retf["sw_current_ver"]    = $ret["result"]->software[0]->currentSoftwareVersion;
    $retf["sw_available_ver"]  = $ret["result"]->software[0]->update[0]->updateVersion;
    $retf["sw_available_date"] = $ret["result"]->software[0]->update[0]->updateDate;
    $retf["sw_available_size"] = $ret["result"]->software[0]->update[0]->updateSize;
    $retf["sw_available_UpURL"] = $ret["result"]->software[0]->update[0]->updateURL;
    $retf["sw_available_LiURL"] = $ret["result"]->software[0]->update[0]->licenseURL;
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    return($retf);
  }
}
?>
