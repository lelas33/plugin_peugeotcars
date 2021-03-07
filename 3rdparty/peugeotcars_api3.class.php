<?php

// Fonctions de connexion aux API PSA
// ==================================
class peugeotcars_api3 {

  // Constantes pour la classe
  protected $url_api_psa_oauth1        = 'https://id-dcr.peugeot.com/';
  protected $url_api_psa_oauth2        = 'https://idpcvs.peugeot.com/am/oauth2/';
  protected $url_api_psa_conn_car      = 'https://api.groupe-psa.com/connectedcar/v4/';
  protected $url_api_psa_mym_sgp       = 'https://ap-mym.servicesgp.mpsa.com/api/v1/';
  protected $url_api_sw                = 'https://api.groupe-psa.com/applications/majesticf/v1/';


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
  protected $access_token_mym;
  protected $debug_api;


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
    $this->debug_api = false;
  }

  // Check login state (Tokens still allowed)
  function state_login()
  {
    if (isset($this->access_token["access_token"]) && isset($this->access_token["access_token_ts"]) && isset($this->access_token["access_token_dur"])) {
      $ctime = time();
      //printf("login:ctime=".$ctime."\n");
      if (($ctime >= $this->access_token["access_token_ts"]) && ($ctime < ($this->access_token["access_token_ts"] + $this->access_token["access_token_dur"] - 15))) {
        return(1);  // no need for new login
      }
    }
    else
      return (0);
  }

  // Set debug mode
  function set_debug_api()
  {
    $this->debug_api = true;
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

  // ======================================================
  // Functions dedicated to API ap-mym.servicesgp.mpsa.com
  // ======================================================
  // GET HTTP command: not used
  
  // POST HTTP command
  private function post_ap_mym_servicesgp($param, $fields = null)
  {
    $session = curl_init();

    curl_setopt($session, CURLOPT_URL, $this->url_api_psa_mym_sgp.$param);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Version: 1.23.4',
      'Token: ' . $this->access_token_mym));
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

  // ===================================================
  // Connection aux API: api.groupe-psa.com/connectedcar
  // ===================================================
  // Login pour l'API: authentification
  function pg_api_login()
  {
    $form = "grant_type=password&username=".urlencode($this->username)."&password=".urlencode($this->password)."&scope=openid profile&realm=clientsB2CPeugeot";
    $param = "access_token";
    $ret = $this->post_api_psa_auth2($param, $form);
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

  // Get vehicle
  function pg_api_vehicles($vin)
  {
    $param = "user/vehicles?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    if ($this->debug_api)
      var_dump($ret["result"]);
    $retf = [];
    $retf["success"] = "KO";
    if (isset($ret["result"]->total)) {
      $nb_veh = $ret["result"]->total;
      // recherche du vin dans la liste des vehicules associés à ce compte
      if ($nb_veh >= 1) {
        for ($veh=0; $veh<$nb_veh; $veh++) {
          if ($vin == $ret["result"]->_embedded->vehicles[$veh]->vin) {
            $this->vehicle_id = $ret["result"]->_embedded->vehicles[$veh]->id;
            $retf["pictures"] = $ret["result"]->_embedded->vehicles[$veh]->pictures;
            $retf["success"] = "OK";
          }
        }
      }
    }
    return($retf);
  }

  // convertion format date
  function ISO8601ToSeconds($ISO8601)
  {
    preg_match('/\d{1,2}[H]/', $ISO8601, $hours);
    preg_match('/\d{1,2}[M]/', $ISO8601, $minutes);
    preg_match('/\d{1,2}[S]/', $ISO8601, $seconds);
    $duration = [
      'hours'   => $hours ? $hours[0] : 0,
      'minutes' => $minutes ? $minutes[0] : 0,
      'seconds' => $seconds ? $seconds[0] : 0,
    ];
    $hours   = substr($duration['hours'], 0, -1);
    $minutes = substr($duration['minutes'], 0, -1);
    $seconds = substr($duration['seconds'], 0, -1);
    $toltalSeconds = ($hours * 60 * 60) + ($minutes * 60) + $seconds;
    return $toltalSeconds;
  }
  
  // convertion format date
  function ISO8601ToHMS($ISO8601)
  {
    preg_match('/\d{1,2}[H]/', $ISO8601, $hours);
    preg_match('/\d{1,2}[M]/', $ISO8601, $minutes);
    preg_match('/\d{1,2}[S]/', $ISO8601, $seconds);
    $duration = [
      'hours'   => $hours ? $hours[0] : 0,
      'minutes' => $minutes ? $minutes[0] : 0,
      'seconds' => $seconds ? $seconds[0] : 0,
    ];
    $hms = [];
    $hms['H'] = substr($duration['hours'], 0, -1);
    $hms['M'] = substr($duration['minutes'], 0, -1);
    $hms['S'] = substr($duration['seconds'], 0, -1);
    return $hms;
  }

  // Get vehicule status
  function pg_api_vehicles_status()
  {
    $param = "user/vehicles/".$this->vehicle_id."/status?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    if ($this->debug_api)
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
    $retf["gps_lon"] = 0;
    $retf["gps_lat"] = 0;
    $retf["gps_alt"] = 0;
    if (count($ret["result"]->lastPosition->geometry->coordinates) >= 2) {
      $retf["gps_lon"]  = $ret["result"]->lastPosition->geometry->coordinates[0];
      $retf["gps_lat"]  = $ret["result"]->lastPosition->geometry->coordinates[1];
    }
    if (count($ret["result"]->lastPosition->geometry->coordinates) >= 3)
      $retf["gps_alt"] = $ret["result"]->lastPosition->geometry->coordinates[2];
    if (isset($ret["result"]->lastPosition->properties->signalQuality))
      $retf["conn_level"]   = $ret["result"]->lastPosition->properties->signalQuality;
    $retf["batt_voltage"] = intval($ret["result"]->battery->voltage);
    $retf["batt_current"] = intval($ret["result"]->battery->current);
    $retf["precond_status"] = $ret["result"]->preconditionning->airConditioning->status;
    $veh_type = strtolower ($ret["result"]->service->type);
    $retf["service_type"]  = $veh_type;
    $retf["kinetic_moving"]= $ret["result"]->kinetic->moving;
    $retf["gen_mileage"]   = $ret["result"]->{"timed.odometer"}->mileage;
    // Retours energie electrique & fuel si hybride
    if (count($ret["result"]->energy) == 1) {
      $elec_id = 0;
      $fuel_id = 0;
    }
    else if (count($ret["result"]->energy) == 2) {
      if (strtolower($ret["result"]->energy[0]->type) == "electric") {
        $elec_id = 0;
        $fuel_id = 1;
      }
      else {
        $elec_id = 1;
        $fuel_id = 0;
      }
    }
    else {
      $elec_id = 0;
      $fuel_id = 0;
    }

    $retf["batt_level"]   = $ret["result"]->energy[$elec_id]->level;
    $retf["batt_autonomy"]= $ret["result"]->energy[$elec_id]->autonomy;
    $retf["charging_plugged"] = $ret["result"]->energy[$elec_id]->charging->plugged;
    // status chargement batterie
    $ch_sts  = $ret["result"]->energy[$elec_id]->charging->status;
    $ch_rem_tm = $ret["result"]->energy[$elec_id]->charging->remainingTime;
    $ch_rem_tm = $this->ISO8601ToSeconds($ch_rem_tm);
    $ch_rate = intval($ret["result"]->energy[$elec_id]->charging->chargingRate);
    $ch_update = strtotime($ret["result"]->energy[$elec_id]->updatedAt);
    if ((strtolower($ch_sts) == "inprogress") && ($ch_rem_tm != 0)) {
      $end_charging = $ch_update + $ch_rem_tm;
    }
    else {
      $end_charging = 0;
    }
    $retf["charging_status"] = $ch_sts;
    $retf["charging_remain_time"] = ($ch_rem_tm == 0)?"--":date("H:i", $ch_rem_tm-3600);
    $retf["charging_end_time"] = ($end_charging == 0)?"--":date("H:i", $end_charging);
    $retf["charging_rate"] = $ch_rate;
    $retf["charging_mode"] = $ret["result"]->energy[$elec_id]->charging->chargingMode;

    // Retours energie carburant si vehicule hybride
    $retf["fuel_level"]   = $ret["result"]->energy[$fuel_id]->level;
    $retf["fuel_autonomy"]= $ret["result"]->energy[$fuel_id]->autonomy;

    return $retf;
  }

  // Get vehicule Preconditionning programs
  function pg_api_vehicles_precond()
  {
    $param = "user/vehicles/".$this->vehicle_id."/status?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    if ($this->debug_api)
      var_dump($ret["result"]);
    $retf = [];

    $retf["pr_status"] = $ret["result"]->preconditionning->airConditioning->status;
    if (isset($ret["result"]->preconditionning->airConditioning->programs)) {
      $retf["pp_no_prog"] = 0;
      $retf["pp_active_number"] = count($ret["result"]->preconditionning->airConditioning->programs);
      for ($prog=0; $prog<$retf["pp_active_number"]; $prog++) {
        $retf["pp_prog"][$prog]["slot"] = $ret["result"]->preconditionning->airConditioning->programs[$prog]->slot;
        $retf["pp_prog"][$prog]["enabled"] = ($ret["result"]->preconditionning->airConditioning->programs[$prog]->enabled)?1:0;
        $hms_start = $this->ISO8601ToHMS($ret["result"]->preconditionning->airConditioning->programs[$prog]->start);
        $retf["pp_prog"][$prog]["hour"] = $hms_start['H'];
        $retf["pp_prog"][$prog]["minute"] = $hms_start['M'];
        $pp_days = $ret["result"]->preconditionning->airConditioning->programs[$prog]->occurence->day;
        $retf["pp_prog"][$prog]["day"] = [0,0,0,0,0,0,0];
        foreach ($pp_days as $day) {
          if     ($day == "Mon") $retf["pp_prog"][$prog]["day"][0] = 1;
          elseif ($day == "Tue") $retf["pp_prog"][$prog]["day"][1] = 1;
          elseif ($day == "Wed") $retf["pp_prog"][$prog]["day"][2] = 1;
          elseif ($day == "Thu") $retf["pp_prog"][$prog]["day"][3] = 1;
          elseif ($day == "Fri") $retf["pp_prog"][$prog]["day"][4] = 1;
          elseif ($day == "Sat") $retf["pp_prog"][$prog]["day"][5] = 1;
          elseif ($day == "Sun") $retf["pp_prog"][$prog]["day"][6] = 1;
        }
      }
    }
    else {
      $retf["pp_no_prog"] = 1;
    }

    return $retf;
  }

  // For tests only
  function pg_api_vehicles_test()
  {
//    $param = "user/vehicles/".$this->vehicle_id."/maintenance?client_id=".$this->client_id;
//    $param = "user/vehicles/".$this->vehicle_id."/telemetry?client_id=".$this->client_id;
//    $param = "user/vehicles/".$this->vehicle_id."/lastPosition?client_id=".$this->client_id;
    $param = "user/vehicles/".$this->vehicle_id."/alerts?client_id=".$this->client_id;
//    $param = "user/vehicles/".$this->vehicle_id."/status?client_id=".$this->client_id;
    $ret = $this->get_api_psa_conn_car($param);
    //var_dump($ret["info"]);
    if ($this->debug_api)
      var_dump($ret["result"]);
  }


  // =========================================
  // Connection aux API: :ap-mym.servicesgp
  // =========================================
  // Login pour l'API: authentification
  function pg_api_mym_login()
  {
    $json_req = '{"siteCode":"AP_FR_ESP","culture":"fr-FR","action":"authenticate","fields":{"USR_EMAIL":{"value":"'.$this->username.'"},"USR_PASSWORD":{"value":"'.$this->password.'"}}}';
    $param = "mobile-services/GetAccessToken?jsonRequest=".urlencode($json_req);
    //print("PARAM:\n".$param."\n");
    $ret = $this->post_api_psa_auth1($param);
    //var_dump($ret["info"]);
    if ($this->debug_api)
      var_dump($ret["result"]);
    if ($ret["info"]["http_code"] == "200") {
      $this->access_token_mym = $ret["result"]->accessToken;
      //printf("access_token_mym=".$this->access_token_mym."\n");
      return("OK");
    }
    else {
      $this->access_token_mym = "";
      return("KO");
    }
  }

  // Information utilisateur et véhicule
  function pg_ap_mym_user()
  {
    $param = "user?culture=fr_FR&width=1080&cgu=1605377025";
    $fields = '{"site_code": "AP_FR_ESP","ticket": "'.$this->access_token_mym.'"}';
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
    return($retf);
  }

  // Information maintenance du véhicule
  function pg_ap_mym_maintenance($vin)
  {
    $param = "user/vehicles/".$vin."/maintenance?culture=fr_FR&rrdi=".$this->apv_rrdi."&siteGeo=".$this->apv_site_geo;
    $fields = '{"site_code": "AP_FR_ESP","ticket": "'.$this->access_token_mym.'"}';
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
    if (($ret["result"]->requestResult == "OK") && (isset($ret["result"]->software))) {
      $retf["status"]            = "OK";
      $retf["sw_type"]           = $ret["result"]->software[0]->softwareType;
      $retf["sw_current_ver"]    = $ret["result"]->software[0]->currentSoftwareVersion;
      $retf["sw_available_ver"]  = $ret["result"]->software[0]->update[0]->updateVersion;
      $retf["sw_available_date"] = $ret["result"]->software[0]->update[0]->updateDate;
      $retf["sw_available_size"] = $ret["result"]->software[0]->update[0]->updateSize;
      $retf["sw_available_UpURL"] = $ret["result"]->software[0]->update[0]->updateURL;
      $retf["sw_available_LiURL"] = $ret["result"]->software[0]->update[0]->licenseURL;
    }
    else {
      $retf["status"] = "KO";
    }
    //var_dump($ret["info"]);
    //var_dump($ret["result"]);
    //var_dump($retf);
    return($retf);
  }


}


?>

