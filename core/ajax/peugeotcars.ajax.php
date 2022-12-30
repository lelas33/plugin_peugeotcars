<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api3.class.php';
require_once dirname(__FILE__) . '/../php/mqtt_com.php';

define("MYP_FILES_DIR",  "/../../data/MyPeugeot/");
define("CARS_FILES_DIR", "/../../data/");

global $cars_dt;
global $cars_dt_gps;
global $report;

// =====================================================
// Fonction de lecture de tous les trajets d'une voiture
// =====================================================
function get_car_trips_gps($vin, $ts_start, $ts_end)
{
  global $cars_dt;
  
  // Lecture des trajets
  // -------------------
  // ouverture du fichier de log: trajets
  $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'/trips.log';
  $fcar = fopen($fn_car, "r");

  // lecture des donnees
  $line = 0;
  $cars_dt["trips"] = [];
  $first_ts = time();
  $last_ts  = 0;
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      list($tr_tss, $tr_tse, $tr_ds, $tr_batt) = explode(",", $buffer);
      $tsi_s = intval($tr_tss);
      $tsi_e = intval($tr_tse);
      // selectionne les trajets selon leur date depart&arrive
      if (($tsi_s>=$ts_start) && ($tsi_s<=$ts_end)) {
        $cars_dt["trips"][$line] = $buffer;
        $line = $line + 1;
        // Recherche des ts mini et maxi pour les trajets retenus
        if ($tsi_s<$first_ts)
          $first_ts = $tsi_s;
        if ($tsi_e>$last_ts)
          $last_ts = $tsi_e;
      }
    }
  }
  fclose($fcar);

  // Lecture des points GPS pour ces trajets
  // ---------------------------------------
  // ouverture du fichier de log: points GPS
  $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'/gps.log';
  $fcar = fopen($fn_car, "r");

  // lecture des donnees
  $line = 0;
  $line_all = 0;
  $cars_dt["gps"] = [];
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      $tmp=explode(",", $buffer);
      if (count($tmp) == 7) {
        list($pts_ts, $pts_lat, $pts_lon, $pts_alt, $pts_batt, $pts_mlg, $pts_moving) = $tmp;
        $pts_tsi = intval($pts_ts);
        // selectionne les trajets selon leur date depart&arrive
        if (($pts_tsi>=$first_ts) && ($pts_tsi<=$last_ts)) {
          $cars_dt["gps"][$line] = $buffer;
          $line = $line + 1;
        }
      }
      else {
        log::add('peugeotcars', 'error', 'Ajax:get_car_trips: Erreur dans le fichier gps.log, à la ligne:'.$line_all);
      }
      $line_all = $line_all + 1;
    }
  }
  fclose($fcar);
  // Ajoute les coordonnées du domicile pour utilisation par javascript
  $latitute=config::byKey("info::latitude");
  $longitude=config::byKey("info::longitude");
  $cars_dt["home"] = $latitute.",".$longitude;
  // Ajoute la config de la taille batterie
  $eq = eqLogic::byLogicalId($vin, "peugeotcars");
  if ($eq->getIsEnable()) {
    $cfg_batt_capacity = $eq->getConfiguration("batt_capacity");
    $cars_dt["cfg_batt_capacity"] = intval($cfg_batt_capacity);
  }
  //log::add('peugeotcars', 'debug', 'Ajax:get_car_trips:nb_lines'.$line);
  return;
}


// ========================================================
// Fonction de capture de la position copurante du vehicule
// ========================================================
function get_current_position($vin)
{
  $eq = eqLogic::byLogicalId($vin, "peugeotcars");
  if ($eq->getIsEnable()) {
    $cmd_record_period = $eq->getCmd(null, "record_period");
    $record_period = $cmd_record_period->execCmd();
  }
  else {
    return;
  }
  $current_position = [];
  $current_position["status"] = "OK";

  // Login to API
  $last_login_token = $cmd_record_period->getConfiguration('save_auth');
  if ((!isset($last_login_token)) || ($last_login_token == ""))
    $last_login_token = NULL;
  $session_peugeotcars = new peugeotcars_api3();
  $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), config::byKey('brandid', 'peugeotcars'), $last_login_token);
  if ($last_login_token == NULL) {
    $login_token = $session_peugeotcars->pg_api_login();   // Authentification
    if ($login_token["status"] != "OK") {
      log::add('peugeotcars','error',"Erreur Login API PSA");
      return;  // Erreur de login API PSA
    }
    $cmd_record_period->setConfiguration ('save_auth', $login_token);
    $cmd_record_period->save();
    log::add('peugeotcars','debug',"Pas de session en cours => New login");
  }
  else if ($session_peugeotcars->state_login() == 0) {
    $login_token = $session_peugeotcars->pg_api_login();   // Authentification
    if ($login_token["status"] != "OK") {
      log::add('peugeotcars','error',"Erreur Login API PSA");
      return;  // Erreur de login API PSA
    }
    $cmd_record_period->setConfiguration ('save_auth', $login_token);
    $cmd_record_period->save();
    log::add('peugeotcars','debug',"Session expirée => New login");
  }
  // Capture du statut du vehicule
  $ret = $session_peugeotcars->pg_api_vehicles($vin);
  if ($ret["success"] == "KO") {
    log::add('peugeotcars','error',"Erreur Login API PSA");
    return;  // Erreur de login API PSA
    }
  // Statut du véhicule
  $ret_sts = $session_peugeotcars->pg_api_vehicles_status();
//  $current_position["veh"]= ($ret_sts["gps_lat"]+(floatval(rand(0,100))/1000)).",".($ret_sts["gps_lon"]+(floatval(rand(0,100))/1000)).",".$ret_sts["gps_alt"];
  $current_position["veh"]= ($ret_sts["gps_lat"]).",".($ret_sts["gps_lon"]).",".$ret_sts["gps_alt"];

  // Ajoute les coordonnées du domicile pour utilisation par javascript
  $latitute=config::byKey("info::latitude");
  $longitude=config::byKey("info::longitude");
  $current_position["home"] = $latitute.",".$longitude;
  // Statut
  if (($ret_sts["gps_lat"] == 0) && ($ret_sts["gps_lon"] == 0))
    $current_position["status"] = "KO";
  return ($current_position);
}
// ===========================================================
// Fourniture des statistiques sur l'ensemble des trajets
// ===========================================================
function get_car_trips_stats($vin)
{
  // config de la taille batterie
  // ----------------------------
  $eq = eqLogic::byLogicalId($vin, "peugeotcars");
  if ($eq->getIsEnable()) {
    $cfg_batt_capacity = floatval($eq->getConfiguration("batt_capacity"));
    $cfg_cots_kwh = floatval($eq->getConfiguration("cost_kwh"));
  }
  else {
    return;
  }

  // Lecture des trajets
  // -------------------
  // ouverture du fichier de log: trajets
  $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'/trips.log';
  $fcar = fopen($fn_car, "r");

  // lecture de l'ensemble des trajets
  $line = 0;
  $trips = [];
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      list($tr_tss, $tr_tse, $tr_ds, $tr_batt) = explode(",", $buffer);
      $tsi_s = intval($tr_tss);
      $tsi_e = intval($tr_tse);
      $trips[$line]["tss"]   = intval($tr_tss);
      $trips[$line]["tse"]   = intval($tr_tse);
      $trips[$line]["dist"]  = floatval($tr_ds);
      $trips[$line]["conso"] = (floatval($tr_batt) * $cfg_batt_capacity)/100.0;  // conso en kWh
      $line = $line + 1;
    }
  }
  fclose($fcar);
  $nb_trips = $line;
  
  // calcul des statistiques par mois
  // --------------------------------
  $trip_stat["dist"]  = [[]];
  $trip_stat["conso"] = [[]];
  $trip_stat["nb_trips"] = $nb_trips;
  $trip_stat["cfg_cost_kwh"] = $cfg_cots_kwh;
  for ($tr=0; $tr<$nb_trips; $tr++) {
    $tss = $trips[$tr]["tss"];
    $year  = intval(date('Y', $tss));  // Year => ex 2020
    $month = intval(date('n', $tss));  // Month => 1-12
    if (isset($trip_stat["dist"][$year][$month])){
      $trip_stat["dist"][$year][$month] += $trips[$tr]["dist"];
    }
    else {
      $trip_stat["dist"][$year][$month] = $trips[$tr]["dist"];
    }
    if (isset($trip_stat["conso"][$year][$month])){
      $trip_stat["conso"][$year][$month] += $trips[$tr]["conso"];
    }
    else {
      $trip_stat["conso"][$year][$month] = $trips[$tr]["conso"];
    }
  }
  return($trip_stat);
}

// ===========================================================
// Fourniture des informations sur le véhicule (selon son VIN)
// ===========================================================
function get_car_infos($vin)
{
  $session_peugeotcars = new peugeotcars_api3();
  $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), config::byKey('brandid', 'peugeotcars'), NULL);
  // $login_ctr = $session_peugeotcars->pg_api_mym_login();   // Authentification
  // $info = [];
  // if ($login_ctr == "OK") {
    // Recuperation de l'info type du vehicule
    // $eqLogic = eqLogic::byLogicalId($vin,'peugeotcars');
    // if (is_object($eqLogic)) {
      // $cmd = $eqLogic->getCmd(null, "veh_type");
      // if (is_object($cmd)) {
        // $veh_type = $cmd->execCmd();
      // }
    // }

    // Section caractéristiques véhicule
    // $ret = $session_peugeotcars->pg_ap_mym_user();
    // if ($ret["success"] == "OK") {
      // log::add('peugeotcars','debug','get_car_infos:success='.$ret["success"]);
      // $info["vin"] = $vin;
      // $info["short_label"] = $ret["short_label"];
      // $info["veh_type"] = $veh_type;
      // $info["lcdv"] = $ret["lcdv"];
      // $info["warranty_start_date"] = date("j-n-Y", intval($ret["warranty_start_date"]));
      // $info["eligibility"] = $ret["eligibility"];
      // $info["types"] = $ret["eligibility"][0];
      // $liste_logiciel = $ret["eligibility"][1];
      // $info["mileage_km"] = $ret["mileage_val"];
      // $info["mileage_ts"] = date("j-n-Y \à G\hi", intval($ret["mileage_ts"]));
    // }
    // else {
      // log::add('peugeotcars','error',"get_car_infos:Erreur d'accès à l'API pour informations sur le véhicule");
    // }
  // }
  // else {
    // log::add('peugeotcars','error',"get_car_infos:Erreur login API pour informations sur le véhicule");
  // }
  
  // Section version logiciels
  //log::add('peugeotcars','debug','get_car_infos:liste_logiciel='.$liste_logiciel);
  // $liste_logiciel = "rcc";
  // if (strpos($liste_logiciel, "rcc") !== FALSE) {

  // recherche la version SW du log RCC
  $ret = $session_peugeotcars->pg_api_sw_updates($vin, "rcc-firmware");
  if ($ret["status"] == "OK") {
    $info["rcc_type"]            = $ret["sw_type"];
    $info["rcc_current_ver"]     = $ret["sw_current_ver"];
    $info["rcc_available_ver"]   = $ret["sw_available_ver"];
    $info["rcc_available_date"]  = $ret["sw_available_date"];
    $info["rcc_available_size"]  = $ret["sw_available_size"];
    $info["rcc_available_UpURL"] = $ret["sw_available_UpURL"];
    $info["rcc_available_LiURL"] = $ret["sw_available_LiURL"];
  }
    
  // recherche la version SW du log GPS
  $ret = $session_peugeotcars->pg_api_sw_updates($vin, "ovip-int-firmware-version");
  if ($ret["status"] == "OK") {
    $info["gps_type"]            = $ret["sw_type"];
    $info["gps_current_ver"]     = $ret["sw_current_ver"];
    $info["gps_available_ver"]   = $ret["sw_available_ver"];
    $info["gps_available_date"]  = $ret["sw_available_date"];
    $info["gps_available_size"]  = $ret["sw_available_size"];
    $info["gps_available_UpURL"] = $ret["sw_available_UpURL"];
    $info["gps_available_LiURL"] = $ret["sw_available_LiURL"];
  }

  // recherche la version SW de la base carte GPS
  $ret = $session_peugeotcars->pg_api_sw_updates($vin, "map-eur");
  if ($ret["status"] == "OK") {
    $info["map_type"]            = $ret["sw_type"];
    $info["map_current_ver"]     = $ret["sw_current_ver"];
    $info["map_available_ver"]   = $ret["sw_available_ver"];
    $info["map_available_date"]  = $ret["sw_available_date"];
    $info["map_available_size"]  = $ret["sw_available_size"];
    $info["map_available_UpURL"] = $ret["sw_available_UpURL"];
    $info["map_available_LiURL"] = $ret["sw_available_LiURL"];
  }

  return $info;
}

// ===========================================================
//      Gestion des programmes de preconditionnement
// ===========================================================
function precond_set_programs($vin, $pp_to, $progs)
{
  if ($pp_to == "file") {
    // Export to file
    $fn_pp = dirname(__FILE__).CARS_FILES_DIR.$vin.'/precond.cfg';
    file_put_contents($fn_pp, $progs, LOCK_EX);
    return("OK");
  }
  else if ($pp_to == "car") {
    // Export to car
    // Test si deamon OK
    // $eq = eqLogic::byLogicalId($vin, "peugeotcars");
    // $deamon_info = $eq->deamon_info();
    // if ($deamon_info['state'] == 'nok') {
      // log::add('peugeotcars', 'info', "Le démon de gestion des commandes vers le véhicule est arrêté: Commande annulée");
      // return;
    // }
    // Creation d'une liaison TCP/IP avec le serveur MQTT
    $socket = mqtt_start_socket ();
    // Construction du message
    // $prog = json_decode($progs);
    // $msg = [];
    // $ack = [];
    // $msg['cmd'] = CMD_PRECOND_PROGS;
    // $msg['nbp'] = 40;
    // $msg['param'] = array_fill (0, 40, 0);
    // $msg['param'][ 0] = $prog->program1->on; $msg['param'][ 1] = $prog->program1->hour; $msg['param'][ 2] = $prog->program1->minute;
    // $msg['param'][10] = $prog->program2->on; $msg['param'][11] = $prog->program2->hour; $msg['param'][12] = $prog->program2->minute;
    // $msg['param'][20] = $prog->program3->on; $msg['param'][21] = $prog->program3->hour; $msg['param'][22] = $prog->program3->minute;
    // $msg['param'][30] = $prog->program4->on; $msg['param'][31] = $prog->program4->hour; $msg['param'][32] = $prog->program4->minute;
    // for ($day=0; $day<7; $day++) {
      // $msg['param'][ 3+$day] = $prog->program1->day[$day];
      // $msg['param'][13+$day] = $prog->program2->day[$day];
      // $msg['param'][23+$day] = $prog->program3->day[$day];
      // $msg['param'][33+$day] = $prog->program4->day[$day];      
    // }
    // Envoi du message de commande
    $cr = mqtt_message_send2($socket, CMD_PRECOND_PROGS, $progs, $ack);
    // Fermeture du socket TCP/IP
    mqtt_end_socket ($socket);
    return("OK");
  }
}

function precond_get_programs($vin, $pp_from)
{
  if ($pp_from == "file") {
    // Read from file
    $fn_pp = dirname(__FILE__).CARS_FILES_DIR.$vin.'/precond.cfg';
    $progs = file_get_contents($fn_pp);
    return($progs);
  }
  else if ($pp_from == "car") {
    // Get status from car

    // Login a l'API PSA
    $session_peugeotcars = new peugeotcars_api3();
    $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), config::byKey('brandid', 'peugeotcars'), NULL);
    $login_token = $session_peugeotcars->pg_api_login();   // Authentification
    if ($login_token["status"] != "OK") {
      log::add('peugeotcars','error',"Erreur Login API PSA");
      return;  // Erreur de login API PSA
    }
    $ret = $session_peugeotcars->pg_api_vehicles($vin);
    if ($ret["success"] == "KO") {
      log::add('peugeotcars','error',"Erreur Login API PSA");
      return;  // Erreur de login API PSA
      }
    // Get stus from car
    $ret = $session_peugeotcars->pg_api_vehicles_precond();
    // Fill the result
    $pp_prog = [];
    for ($prog=1; $prog<=4; $prog++) {
      $pr_name = "program".$prog;
      // init free programm
      $pp_prog[$pr_name] = [];
      $pp_prog[$pr_name]["day"] = [0,0,0,0,0,0,0];
      $pp_prog[$pr_name]["hour"] = 0;
      $pp_prog[$pr_name]["minute"] = 0;
      $pp_prog[$pr_name]["on"] = 0;
      // search if programm exist in car status
      for ($idx=0; $idx<$ret["pp_active_number"]; $idx++){
        if ($ret["pp_prog"][$idx]["slot"] == $prog) {
          $pp_prog[$pr_name]["day"] = $ret["pp_prog"][$idx]["day"];
          $pp_prog[$pr_name]["hour"] = intval($ret["pp_prog"][$idx]["hour"]);
          $pp_prog[$pr_name]["minute"] = intval($ret["pp_prog"][$idx]["minute"]);
          $pp_prog[$pr_name]["on"] = $ret["pp_prog"][$idx]["enabled"];
        }
      }
    }
    $progs = json_encode ($pp_prog);
    return($progs);
  }
}


// ======================================================================
// Fourniture des informations de maintenance du véhicule (selon son VIN)
// ======================================================================
function get_car_maint($vin)
{
  $session_peugeotcars = new peugeotcars_api3();
  $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), config::byKey('brandid', 'peugeotcars'), NULL);
  $login_ctr = $session_peugeotcars->pg_api_mym_login();   // Authentification
  $maint = [];
  if ($login_ctr == "OK") {
    $ret = $session_peugeotcars->pg_ap_mym_maintenance($vin);
    if ($ret["success"] == "OK") {
      log::add('peugeotcars','info',"Mise à jour date maintenance");
      $maint["mileage_km"]         = $ret["mileage_km"];
      $maint["visite1_date"]       = date("j-n-Y", intval($ret["visite1_ts"]));
      $maint["visite1_conditions"] = $ret["visite1_age"]." an(s) ou ".$ret["visite1_mileage"]." kms";
      $maint["visite1_lb_title"]   = $ret["visite1_lb_title"];
      $maint["visite1_lb_body"]    = $ret["visite1_lb_body"];

      $maint["visite2_date"]       = date("j-n-Y", intval($ret["visite2_ts"]));
      $maint["visite2_conditions"] = $ret["visite2_age"]." an(s) ou ".$ret["visite2_mileage"]." kms";
      $maint["visite2_lb_title"]   = $ret["visite2_lb_title"];
      $maint["visite2_lb_body"]    = $ret["visite2_lb_body"];

      $maint["visite3_date"]       = date("j-n-Y", intval($ret["visite3_ts"]));
      $maint["visite3_conditions"] = $ret["visite3_age"]." an(s) ou ".$ret["visite3_mileage"]." kms";
      $maint["visite3_lb_title"]   = $ret["visite3_lb_title"];
      $maint["visite3_lb_body"]    = $ret["visite3_lb_body"];

      // Mise à jour des paramètres du plugin "peugeotcars"
      $eq = eqLogic::byLogicalId($vin, "peugeotcars");
      if ($eq->getIsEnable()) {
        // jours jusqu'à la prochaine visite
        $nbj_ts = round ((intval($ret["visite1_ts"]) - time())/(24*3600));
        $cmd = $eq->getCmd(null, "entretien_jours");
        $cmd->event($nbj_ts);
        // km jusqu'à la prochaine visite
        $dist = intval($ret["visite1_mileage"]) - intval($ret["mileage_km"]);
        $cmd = $eq->getCmd(null, "entretien_dist");
        $cmd->event($dist);
      }
    }
    else {
      log::add('peugeotcars','error',"get_car_maint:Erreur d'accès à l'API pour informations de maintenance");
    }
  }
  else {
    log::add('peugeotcars','error',"get_car_maint:Erreur login API pour informations de maintenance");
  }
  return $maint;
}


// =====================================
// Gestion des commandes recues par AJAX
// =====================================
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

	ajax::init();

  if (init('action') == 'getTripData') {
    log::add('peugeotcars', 'info', 'Ajax:getTripData');
    $vin = init('eqLogic_id');
    $ts_start = init('param')[0];
    $ts_end   = init('param')[1];
    log::add('peugeotcars', 'debug', 'vin   :'.$vin);
    // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
    get_car_trips_gps($vin, intval ($ts_start), intval ($ts_end));
    $ret_json = json_encode ($cars_dt);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getTripStats') {
    log::add('peugeotcars', 'info', 'Ajax:getTripStats');
    $vin = init('eqLogic_id');
    $trip_stat = get_car_trips_stats($vin);
    $ret_json = json_encode ($trip_stat);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getVehInfos') {
    log::add('peugeotcars', 'info', 'Ajax:getVehInfos');
    $vin = init('eqLogic_id');
    $car_infos = get_car_infos($vin);
    $ret_json = json_encode ($car_infos);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getVehMaint') {
    log::add('peugeotcars', 'info', 'Ajax:getVehMaint');
    $vin = init('eqLogic_id');
    $car_maint = get_car_maint($vin);
    $ret_json = json_encode ($car_maint);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getPreconProgs') {
    $vin = init('eqLogic_id');
    $pp_from = init('pp_from');  // file or car
    log::add('peugeotcars', 'info', 'Ajax:getPreconProgs<='.$pp_from);
    $ret_json = precond_get_programs($vin, $pp_from);
    // log::add('peugeotcars', 'info', 'Ajax:getPreconProgs:'.$ret_json);
    ajax::success($ret_json);
    }

  else if (init('action') == 'setPreconProgs') {
    $vin = init('eqLogic_id');
    $pp_to = init('pp_to');     // file or car
    $progs = init('param');     // programs
    log::add('peugeotcars', 'info', 'Ajax:setPreconProgs=>'.$pp_to);
    $res = precond_set_programs($vin, $pp_to, $progs);
    ajax::success($res);
    }
    
  else if (init('action') == 'getCurrentPosition') {
    $vin = init('eqLogic_id');
    log::add('peugeotcars', 'info', 'Ajax:getCurrentPosition');
    $current_position = get_current_position($vin);
    $ret_json = json_encode ($current_position);
    ajax::success($ret_json);
    }

  else if (init('action') == 'OTP_Prepare') {
    log::add('peugeotcars', 'info', 'Ajax:OTP_Prepare');
    $mail   = config::byKey('account', 'peugeotcars');
    $passwd = config::byKey('password', 'peugeotcars');
    $brandid= config::byKey('brandid', 'peugeotcars');
    $country= config::byKey('country', 'peugeotcars');
    log::add('peugeotcars', 'info', 'Ajax:Params:'.$mail."/".$passwd."/".$brandid."/".$country);
    ajax::success($ret_json);
    }

  else if (init('action') == 'OTP_ReqSMS') {
    log::add('peugeotcars', 'info', 'Ajax:OTP_ReqSMS');
    ajax::success($ret_json);
    }

  else if (init('action') == 'OTP_Finalize') {
    log::add('peugeotcars', 'info', 'Ajax:OTP_Finalize');
    ajax::success($ret_json);
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
