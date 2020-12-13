<?php

// Fonctions de connexion aux API PSA
// ==================================
class peugeotcars_api3 {

  // Constantes pour la classe
  protected $url_api_psa_oauth         = 'https://idpcvs.peugeot.com/am/oauth2/';
  protected $url_api_psa_conn_car      = 'https://api.groupe-psa.com/connectedcar/v4/';


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


  // =====================================
  // Functions dedicated to API psa_auth2
  // =====================================
  // GET HTTP command : unsused
  
  // POST HTTP command
  private function post_api_psa_auth($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_oauth.$param);
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
      'Accept: application/hal+json',
      'Authorization: Bearer ' . $this->access_token["access_token"],
      'x-introspect-realm: clientsB2CPeugeot'));
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
      'Accept: application/hal+json',
      'Authorization: Bearer ' . $this->access_token["access_token"],
      'x-introspect-realm: clientsB2CPeugeot'));
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



  // ==========================================
  // Connection aux API PSU : authentification
  // ==========================================
  
  function pg_api_login()
  {
    $form = "grant_type=password&username=".urlencode($this->username)."&password=".urlencode($this->password)."&scope=openid profile&realm=clientsB2CPeugeot";
    $param = "access_token";
    $ret = $this->post_api_psa_auth($param, $form);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    $this->access_token = [];
    if ($ret["info"]["http_code"] == "200") {
      $this->access_token["access_token"]  = $ret["result"]->access_token;
      $this->access_token["refresh_token"] = $ret["result"]->refresh_token;
      $this->access_token["access_token_ts"]  = time();  // token consented on
      $this->access_token["access_token_dur"] = intval($ret["result"]->expires_in);
      $this->access_token["status"] = "OK";
    }
    else {
      $this->access_token["status"] = "KO";
    }
    return($this->access_token);  // new login performed
  }

  // ===================================================
  // Connection aux API: api.groupe-psa.com/connectedcar
  // ===================================================
  function pg_api_vehicles($vin)
  {
    $param = "user/vehicles?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    $retf = [];
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
    $param = "user/vehicles/".$this->vehicle_id."/status?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    var_dump($ret["result"]);
    // For trace analysis
    // $fn_log_sts = "/var/www/html/plugins/peugeotcars/data/car_log.txt";
    // $date = date("Y-m-d H:i:s");
    // $log_dt = $date." => ";
    // file_put_contents($fn_log_sts, $log_dt, FILE_APPEND | LOCK_EX);
    // $log_dt = json_encode ($ret["result"])."\n";
    // file_put_contents($fn_log_sts, $log_dt, FILE_APPEND | LOCK_EX);
    // end trace analysis
    $retf = [];
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

}

?>

