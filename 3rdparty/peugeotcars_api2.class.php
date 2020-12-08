<?php

// Fonctions de connexion aux API PSA
// ==================================
class peugeotcars_api_v2 {

  // Constantes pour la classe
  protected $url_api_custom_gateway_v1 = 'https://api-custom-gateway.mym.awsmpsa.com/bo/v1/';
  protected $url_api_me                = 'https://api-me.mym.awsmpsa.com/v1/';
  protected $url_api_car               = 'https://api-car.mym.awsmpsa.com/v1/';


  protected $url_api_psa_oauth1        = 'https://id-dcr.peugeot.com/';
  protected $url_api_psa_oauth2        = 'https://idpcvs.peugeot.com/am/oauth2/';
  protected $url_api_psa_appli_cvs     = 'https://api.groupe-psa.com/applications/cvs/v1/';
  protected $url_api_psa_appli_miseco  = 'https://api.groupe-psa.com/applications/miseco/v1/';
  protected $url_api_sw                = 'https://api.groupe-psa.com/applications/majesticf/v1/';
  protected $url_api_psa_conn_car      = 'https://api.groupe-psa.com/connectedcar/v3/user/';
  protected $url_api_psa_mym_sgp       = 'https://ap-mym.servicesgp.mpsa.com/api/v1/';

  protected $client_id_b64     = "MWVlYmMyZDUtNWRmMy00NTliLWE2MjQtMjBhYmZjZjgyNTMw";
  protected $client_secret_b64 = "VDV0UDdpUzBjTzhzQzBsQTJpRTJhUjdnSzZ1RTVyRjNsSjhwQzNuTzFwUjd0TDh2VTE=";

  protected $username;
  protected $password;
  protected $client_id;
  protected $client_secret;
  protected $access_token = [];
  protected $vehicle_id;
  protected $user_id;
  protected $apv_site_geo;
  protected $apv_rrdi;


  // ==============================
  // General function : login
  // ==============================
  function login($username, $password, $token)
  {
    $this->username = $username;
    $this->password = $password;
    $this->access_token = $token;  // Etat des token des appels précédents
    $this->client_id     = base64_decode ($this->client_id_b64);
    $this->client_secret = base64_decode ($this->client_secret_b64);
  }

  // ====================================
  // Functions dedicated to API psa_auth1
  // ====================================
  // GET HTTP command : unsused
  
  // POST HTTP command
  private function post_api_psa_auth1($param)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_oauth1.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
       'Content-type: application/json',
       'Accept: application/json'));
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
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


  // =========================================
  // Functions dedicated to API psa_appli_cvs
  // =========================================
  // GET HTTP command : unsused
  
  // POST HTTP command
  private function post_api_psa_appli_cvs($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_appli_cvs.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'));
    curl_setopt($session, CURLOPT_POST, true);
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

  // =====================================
  // Functions dedicated to API psa_auth2
  // =====================================
  // GET HTTP command : unsused
  
  // POST HTTP command
  private function post_api_psa_auth2($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_oauth2.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/x-www-form-urlencoded',
       'Authorization: Basic '.base64_encode($this->client_id.":".$this->client_secret),
       'Accept: application/json'));
    curl_setopt($session, CURLOPT_POST, true);
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
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }

  // ========================================
  // Functions dedicated to API psa_conn_car
  // ========================================
  // GET HTTP command
  private function get_api_psa_conn_car($param, $fields = null)
  {
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_conn_car.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->access_token["access_token2"]));
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
  private function post_api_psa_conn_car($param, $fields = null)
  {
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_conn_car.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->access_token["access_token2"]));
    curl_setopt($session, CURLOPT_POST, true);
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
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }


  // ======================================================
  // Functions dedicated to API ap-mym.servicesgp.mpsa.com
  // ======================================================
  // GET HTTP command
  private function get_ap_mym_servicesgp($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_mym_sgp.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Version: 1.23.4',
      'Token: ' . $this->access_token["access_token1"]));
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
  private function post_ap_mym_servicesgp($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_mym_sgp.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Version: 1.23.4',
      'Token: ' . $this->access_token["access_token1"]));
    curl_setopt($session, CURLOPT_POST, true);
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
//    throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }


  // =============================
  // Functions dedicated to api_sw
  // =============================
  // GET HTTP command (not used)
  // POST HTTP command
  private function post_api_sw($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_sw.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Accept: application/json, text/plain, */*'));
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
    $ret_array["info"] = $info;
    $ret_array["result"] = json_decode($json);
    return $ret_array;
  }

  // ==========================================
  // Connection aux API PSU : authentification
  // ==========================================
  function pg_api_login1()
  {
    $json_req = '{"siteCode":"AP_FR_ESP","culture":"fr-FR","action":"authenticate","fields":{"USR_EMAIL":{"value":"'.$this->username.'"},"USR_PASSWORD":{"value":"'.$this->password.'"}}}';
    $param = "mobile-services/GetAccessToken?jsonRequest=".urlencode($json_req);
    print("PARAM:\n".$param."\n");
    $ret = $this->post_api_psa_auth1($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    $this->access_token["access_token1"] = $ret["result"]->accessToken;
    $this->access_token["access_token1_ts"]  = date();  // token consented on
    $this->access_token["access_token1_dur"] = 3600;    // For the duration (fixed 1h)
    printf("access_token1=".$this->access_token["access_token1"]."\n");
    //return $retf;
  }

  function pg_api_login2()
  {
    $form = "client_id=".$this->client_id."&grant_type=password&client_secret=".$this->client_secret."&username=".urlencode("AP#".$this->username)."&password=".urlencode($this->password)."&scope=public";
    $param = "oauth2/token";
    print("PARAM:\n".$param."\n");
    $ret = $this->post_api_psa_appli_cvs($param, $form);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    $this->access_token["access_token2"] = $ret["result"]->access_token;
    $this->access_token["access_token2_ts"]  = $ret["result"]->consented_on;  // token consented on
    $this->access_token["access_token2_dur"] = $ret["result"]->expires_in;    // For the duration (fixed 1h)
    printf("access_token2=".$this->access_token["access_token2"]."\n");
    //return $retf;
  }

  // Login a l'API peugeot car connect v3
  function pg_api_login1_2()
  {
    // Login step 1
    $this->access_token = [];
    $json_req = '{"siteCode":"AP_FR_ESP","culture":"fr-FR","action":"authenticate","fields":{"USR_EMAIL":{"value":"'.$this->username.'"},"USR_PASSWORD":{"value":"'.$this->password.'"}}}';
    $param = "mobile-services/GetAccessToken?jsonRequest=".urlencode($json_req);
    $ret = $this->post_api_psa_auth1($param);
    //var_dump($ret["result"]);
    if (isset($ret["result"]->accessToken)) {
      $this->access_token["access_token1"] = $ret["result"]->accessToken;
      $this->access_token["access_token1_ts"]  = time();  // token consented on
      $this->access_token["access_token1_dur"] = 3600;    // For the duration (fixed 1h)
      $this->access_token["status"] = "OK";
      //printf("access_token1=".$this->access_token["access_token1"]."\n");
    }
    else {
      $this->access_token["status"] = "KO";
      return($this->access_token);  // new login performed
    }
    // Login step 2
    $form = "client_id=".$this->client_id."&grant_type=password&client_secret=".$this->client_secret."&username=".urlencode("AP#".$this->username)."&password=".urlencode($this->password)."&scope=public";
    $param = "oauth2/token";
    $ret = $this->post_api_psa_appli_cvs($param, $form);
    //var_dump($ret["result"]);
    if (isset($ret["result"]->access_token)) {
      $this->access_token["access_token2"]     = $ret["result"]->access_token;
      $this->access_token["access_token2_ts"]  = $ret["result"]->consented_on;  // token consented on
      $this->access_token["access_token2_dur"] = $ret["result"]->expires_in;    // For the duration (fixed 1h)
      $this->access_token["status"] = "OK";
      //printf("access_token2=".$this->access_token["access_token2"]."\n");
    }
    else {
      $this->access_token["status"] = "KO";
    }
    return($this->access_token);  // new login performed
  }
  
  // Check login state (Tokens still allowed)
  function state_login()
  {
    if (isset($this->access_token["access_token1"]) && isset($this->access_token["access_token1_ts"]) && isset($this->access_token["access_token1_dur"]) &&
        isset($this->access_token["access_token2"]) && isset($this->access_token["access_token2_ts"]) && isset($this->access_token["access_token2_dur"])) {
      $ctime = time();
      //printf("login:ctime=".$ctime."\n");
      if (($ctime >= $this->access_token["access_token1_ts"]) && ($ctime < ($this->access_token["access_token1_ts"] + $this->access_token["access_token1_dur"] - 15)) &&
          ($ctime >= $this->access_token["access_token2_ts"]) && ($ctime < ($this->access_token["access_token2_ts"] + $this->access_token["access_token2_dur"] - 15))) {
        return(1);  // no need for new login
      }
    }
    else
      return (0);
  }
  
  function pg_api_login3()
  {
    $form = "grant_type=password&username=".urlencode($this->username)."&password=".urlencode($this->password)."&scope=openid+profile";
    $param = "access_token";
    print("PARAM:\n".$param."\n");
    $ret = $this->post_api_psa_auth2($param, $form);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // ===================================================
  // Connection aux API: api.groupe-psa.com/connectedcar
  // ===================================================
  function pg_api_vehicles($vin)
  {
    $param = "vehicles?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    $retf["success"] = "KO";
    $nb_veh = $ret["result"]->total;
    // recherche du vin dans la liste des vehicules associés à ce compte
    if ($ret["result"]->total >= 1) {
      for ($veh=0; $veh<$nb_veh; $veh++) {
        if ($vin == $ret["result"]->_embedded->vehicles[$veh]->vin) {
          $this->vehicle_id = $ret["result"]->_embedded->vehicles[$veh]->id;
          $retf["pictures"] = $ret["result"]->_embedded->vehicles[$veh]->pictures;
          $retf["success"] = "OK";
        }
      }
    }
    return($retf);
  }

  function pg_api_vehicles_status()
  {
    $param = "vehicles/".$this->vehicle_id."/status?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    // For trace analysis
    // $fn_log_sts = "/var/www/html/plugins/peugeotcars/data/car_log.txt";
    // $date = date("Y-m-d H:i:s");
    // $log_dt = $date." => ";
    // file_put_contents($fn_log_sts, $log_dt, FILE_APPEND | LOCK_EX);
    // $log_dt = json_encode ($ret["result"])."\n";
    // file_put_contents($fn_log_sts, $log_dt, FILE_APPEND | LOCK_EX);
    // end trace analysis
    $retf["gps_lon"]  = $ret["result"]->lastPosition->geometry->coordinates[0];
    $retf["gps_lat"]  = $ret["result"]->lastPosition->geometry->coordinates[1];
    $retf["gps_head"] = $ret["result"]->lastPosition->geometry->coordinates[2];
    $retf["conn_level"]   = $ret["result"]->lastPosition->properties->signalQuality;
    $retf["batt_level"]   = $ret["result"]->energy[0]->level;
    $retf["batt_autonomy"]= $ret["result"]->energy[0]->autonomy;
    $retf["batt_voltage"] = $ret["result"]->battery->voltage;
    $retf["batt_current"] = $ret["result"]->battery->current;
    $retf["precond_status"] = $ret["result"]->preconditionning->airConditioning->status;
    $retf["charging_plugged"] = $ret["result"]->energy[0]->charging->plugged;
    $retf["charging_status"] = $ret["result"]->energy[0]->charging->status;
    $tmp = $ret["result"]->energy[0]->charging->remainingTime;
    $tmp = strtolower(substr($tmp, 2));  // modification format "charging_remain_time" : PT8H40M => 8h40m
    $retf["charging_remain_time"] = $tmp;
    $retf["charging_rate"] = $ret["result"]->energy[0]->charging->chargingRate;
    $retf["charging_mode"] = $ret["result"]->energy[0]->charging->chargingMode;
    $retf["kinetic_moving"]= $ret["result"]->kinetic->moving;
    $retf["gen_mileage"]   = $ret["result"]->{"timed.odometer"}->mileage;
    return $retf;
  }

  // Retour des trajets effectués par le vehicule (pas de retour de cette fonction !!)
  function pg_api_vehicles_trips()
  {
    $param = "vehicles/".$this->vehicle_id."/trips?client_id=".$this->client_id;
    print("PARAM:\n".$param."\n");
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // Retour des alertes sur le vehicule (pas de retour de cette fonction !!)
  function pg_api_vehicles_alerts()
  {
    $param = "vehicles/".$this->vehicle_id."/alerts?client_id=".$this->client_id;
    print("PARAM:\n".$param."\n");
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // derniere position GPS connue du vehicule (Info identique a status)
  function pg_api_vehicles_last_position()
  {
    $param = "vehicles/".$this->vehicle_id."/lastPosition?client_id=".$this->client_id;
    print("PARAM:\n".$param."\n");
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // Retour des telemetries sur le vehicule (pas de retour de cette fonction !!)
  function pg_api_vehicles_telemetry()
  {
    $param = "vehicles/".$this->vehicle_id."/telemetry?client_id=".$this->client_id;
    print("PARAM:\n".$param."\n");
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // Info de maintenance du vehicule (Info identique a status)
  function pg_api_vehicles_maintenance()
  {
    $param = "vehicles/".$this->vehicle_id."/maintenance?client_id=".$this->client_id;
    print("PARAM:\n".$param."\n");
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    //return $retf;
  }

  // ====================================================
  // Information disponibles par l'API:ap-mym.servicesgp
  // ====================================================
  // Information utilisateur et véhicule
  function pg_ap_mym_user()
  {
    $param = "user?culture=fr_FR&width=1080&cgu=1605377025";
    $fields = '{"site_code": "AP_FR_ESP","ticket": "'.$this->access_token["access_token1"].'"}';
    $ret = $this->post_ap_mym_servicesgp($param, $fields);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    $retf = [];
    if (isset($ret["result"]->success)) {
      // proprietaire
      $retf["success"]     = "OK";
      $retf["apv_name"]    = $ret["result"]->success->dealers->apv->name;
      $retf["apv_address"] = $ret["result"]->success->dealers->apv->address->address1;
      $retf["apv_city"]    = $ret["result"]->success->dealers->apv->address->zip_code . " " . $ret["result"]->success->dealers->apv->address->city;
      // vehicule
      $retf["vin"]  = $ret["result"]->success->vehicles[0]->vin;
      $retf["lcdv"] = $ret["result"]->success->vehicles[0]->lcdv;
      $retf["short_label"] = $ret["result"]->success->vehicles[0]->short_label;
      $retf["warranty_start_date"] = $ret["result"]->success->vehicles[0]->warranty_start_date;
      $retf["visual"] = $ret["result"]->success->vehicles[0]->visual;
      $retf["eligibility"] = $ret["result"]->success->vehicles[0]->eligibility;
      $retf["mileage_val"] = $ret["result"]->success->vehicles[0]->mileage->value;
      $retf["mileage_ts"] = $ret["result"]->success->vehicles[0]->mileage->timestamp;

      // Additional parameters stored for further API requests
      $this->user_id      = $ret["result"]->success->id;
      $this->apv_site_geo = $ret["result"]->success->dealers->apv->site_geo;
      $this->apv_rrdi     = $ret["result"]->success->dealers->apv->rrdi;
    }
    else {
      $retf["success"]     = "KO";
    }
    //var_dump($retf);
    return($retf);
  }

  // Information maintenance du véhicule
  function pg_ap_mym_maintenance($vin)
  {
    $param = "user/vehicles/".$vin."/maintenance?culture=fr_FR&rrdi=".$this->apv_rrdi."&siteGeo=".$this->apv_site_geo;
    $fields = '{"site_code": "AP_FR_ESP","ticket": "'.$this->access_token["access_token1"].'"}';
    $ret = $this->post_ap_mym_servicesgp($param, $fields);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    $retf = [];
    if (isset($ret["result"]->success)) {
      $retf["success"]     = "OK";
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
      $retf["success"]     = "KO";
    }
    //var_dump($retf);
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
    $retf = [];
    if ($ret["result"]->requestResult == "OK") {
      $retf["sw_type"]           = $ret["result"]->software[0]->softwareType;
      $retf["sw_current_ver"]    = $ret["result"]->software[0]->currentSoftwareVersion;
      $retf["sw_available_ver"]  = $ret["result"]->software[0]->update[0]->updateVersion;
      $retf["sw_available_date"] = $ret["result"]->software[0]->update[0]->updateDate;
      $retf["sw_available_size"] = $ret["result"]->software[0]->update[0]->updateSize;
      $retf["sw_available_UpURL"] = $ret["result"]->software[0]->update[0]->updateURL;
      $retf["sw_available_LiURL"] = $ret["result"]->software[0]->update[0]->licenseURL;
    }
    // var_dump($ret["info"]);
    // var_dump($ret["result"]);
    //var_dump($retf);
    return($retf);
  }

}
?>

