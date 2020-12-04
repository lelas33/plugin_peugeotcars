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

require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api2.class.php';

define("MYP_FILES_DIR",  "/../../data/MyPeugeot/");
define("CARS_FILES_DIR", "/../../data/");

global $cars_dt;
global $cars_dt_gps;
global $report;
global $car_infos;

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
  $cars_dt["gps"] = [];
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      list($pts_ts, $pts_lat, $pts_lon, $pts_head, $pts_batt, $pts_mlg, $pts_moving) = explode(",", $buffer);
      $pts_tsi = intval($pts_ts);
      // selectionne les trajets selon leur date depart&arrive
      if (($pts_tsi>=$first_ts) && ($pts_tsi<=$last_ts)) {
        $cars_dt["gps"][$line] = $buffer;
        $line = $line + 1;
      }
    }
  }
  fclose($fcar);

  //log::add('peugeotcars', 'debug', 'Ajax:get_car_trips:nb_lines'.$line);
  return;
}

// =================================================================
// Fonction de verification de la presence d'un trajet dans la base
// =================================================================
function trip_is_in_base($vin, $id, $ts_start)
{
  global $cars_dt;
  
  // parcours du tableau des enregistrements
  //log::add('peugeotcars', 'debug', 'Ajax:trip_is_in_base:'.$vin."/".$id."/".$ts_start);
  foreach ($cars_dt["log"] as $trip) {
    list($tr_id, $tr_st, $tr_et, $tr_sm, $tr_em, $tr_ds, $tr_mdy, $tr_mds) = explode(",", $trip);
    //log::add('peugeotcars', 'debug', 'Ajax:trip_is_in_base:'.$tr_id."/".$id);
    if ((intval($tr_id) === intval($id)) && (intval($ts_start) === intval($tr_st)))
      return ($tr_id);    
  }
  return (0);
}


// ===================================================================
// Fonction lecture des donnees de trajets issues de l'appli MyPeugeot
// et lise en place de ces donnees dans une base locale
// ===================================================================
function get_trip_files()
{
  global $report;
  log::add('peugeotcars', 'debug', 'Ajax:get_trip_files');
  $report = [];
  $param_car = [];
  // lecture des fichiers issus de l'appli MyPeugeot
  $path = dirname(__FILE__).MYP_FILES_DIR."*.myp";
  foreach (glob($path) as $file) {
    log::add('peugeotcars', 'info', 'Ajax:get_trip_files:'.$file);
    $string = file_get_contents($file);
    if ($string === false) {
      // deal with error...
      log::add('peugeotcars', 'error', 'Ajax:get_trip_files=> Error1');
    }
    $myp = json_decode($string, true);
    if ($myp === null) {
      // deal with error...
      log::add('peugeotcars', 'error', 'Ajax:get_trip_files=> Error2');
    }
    $nb_cars = count($myp);
    log::add('peugeotcars', 'info', 'Ajax:get_trip_files=>Nb voitures:'.$nb_cars);
    for ($car=0; $car<$nb_cars; $car++) {
      $car_vin = $myp[$car]["vin"];
      if (!array_key_exists($car_vin, $param_car)) {
        $param_car[$car_vin]["added_nb_trip"] = 0;
        $param_car[$car_vin]["last_trip"] = 0;
      }
      log::add('peugeotcars', 'info', 'Ajax:get_trip_files=>VIN:'.$car_vin);
      $nb_trip = count($myp[$car]["trips"]);
      log::add('peugeotcars', 'info', 'Ajax:get_trip_files=>nb_trip:'.$nb_trip);
      // Creation du fichier base de la voiture s'il n'existe pas
      $fn_car = dirname(__FILE__).CARS_FILES_DIR.$car_vin.'.db';
      if (!file_exists ($fn_car))
        $fichier = fopen($fn_car, 'w');
      else {
        // Lecture du fichier base courant (pour ne pas enregistrer les doublons)
        $ts_start = strtotime("1970-01-01");
        $ts_end = time();
        get_car_trips($car_vin, $ts_start, $ts_end);
      }
      // Ajout des voyages dans le fichier
      for ($trip=0; $trip<$nb_trip; $trip++) {
        // verifie si le nouveau trajet est deja dans la base
        if ((trip_is_in_base($car_vin, $myp[$car]["trips"][$trip]["id"], $myp[$car]["trips"][$trip]["startDateTime"]) === 0) && 
            (intval($myp[$car]["trips"][$trip]["endDateTime"]) > intval($myp[$car]["trips"][$trip]["startDateTime"]))) {  // Supprime les trajet erronés
          $data = str_pad($myp[$car]["trips"][$trip]["id"], 6, "0", STR_PAD_LEFT).",".
                  $myp[$car]["trips"][$trip]["startDateTime"].",".
                  $myp[$car]["trips"][$trip]["endDateTime"].",".
                  round(floatval($myp[$car]["trips"][$trip]["startMileage"]),1).",".
                  round(floatval($myp[$car]["trips"][$trip]["endMileage"]),1).",".
                  round(floatval($myp[$car]["trips"][$trip]["distance"]),1).",".
                  $myp[$car]["trips"][$trip]["maintenanceDays"].",".
                  $myp[$car]["trips"][$trip]["maintenanceDistance"]."\n";
          file_put_contents($fn_car, $data, FILE_APPEND | LOCK_EX);
          $param_car[$car_vin]["added_nb_trip"] = $param_car[$car_vin]["added_nb_trip"] + 1;
          // identifie s'il s'agit du dernier trajet en date
          if (intval($myp[$car]["trips"][$trip]["startDateTime"]) > $param_car[$car_vin]["last_trip"]) {
            $param_car[$car_vin]["last_trip"]   = intval($myp[$car]["trips"][$trip]["startDateTime"]);
            $param_car[$car_vin]["endMileage"]  = round(floatval($myp[$car]["trips"][$trip]["endMileage"]),1);
            $param_car[$car_vin]["endDateTime"] = intval($myp[$car]["trips"][$trip]["endDateTime"]);
            $param_car[$car_vin]["maintenanceDays"] = intval($myp[$car]["trips"][$trip]["maintenanceDays"]);
            $param_car[$car_vin]["maintenanceDistance"] = intval($myp[$car]["trips"][$trip]["maintenanceDistance"]);
          }
        }
      }
    }
  }
  // Tri final du fichier généré
  for ($car=0; $car<$nb_cars; $car++) {
    $vin = $myp[$car]["vin"]; 
    $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'.db';
    $data = file($fn_car);
    natsort($data);
    file_put_contents($fn_car, $data);
  }
  
  // Rapport de la lecture des fichiers d'entree
  $rpt_li = 0;
  for ($car=0; $car<$nb_cars; $car++) {
    $report["log"][$rpt_li] = "Ajout de ".$param_car[$car_vin]["added_nb_trip"]." trajets pour le véhicule:".$myp[$car]["vin"]."\n";
    log::add('peugeotcars', 'info', 'Ajax:get_trip_files:'.$report["log"][$rpt_li]);
    $rpt_li += 1;
  }

  // Mise à jour des paramètres du plugin "peugeotcars"
  for ($car=0; $car<$nb_cars; $car++) {
    $vin = $myp[$car]["vin"]; 
    $eq = eqLogic::byLogicalId($vin, "peugeotcars");
    if (($eq->getIsEnable()) && ($param_car[$vin]["last_trip"] !== 0)) {
      $cmd = $eq->getCmd(null, "kilometrage");
      $dt_km = $param_car[$vin]["endDateTime"];
      $dt_kma = date('Y-m-d H:i:s', $dt_km);  // 2020-10-27 11:34:35
      log::add('peugeotcars', 'info', 'date:'.$dt_km.'/'.$dt_kma);
      $cmd->event($param_car[$vin]["endMileage"], $dt_kma);
      $cmd = $eq->getCmd(null, "entretien_jours");
      $cmd->event($param_car[$vin]["maintenanceDays"], $dt_kma);
      $cmd = $eq->getCmd(null, "entretien_dist");
      $cmd->event($param_car[$vin]["maintenanceDistance"], $dt_kma);
      $report["log"][$rpt_li] = "Mise à jour du kilomètrage: ".$param_car[$vin]["endMileage"]; $rpt_li += 1;
      $report["log"][$rpt_li] = "Mise à jour du délai jusqu'à l'entretien: ".$param_car[$vin]["maintenanceDays"]; $rpt_li += 1;
      $report["log"][$rpt_li] = "Mise à jour du nombre de kilomètre jusqu'à l'entretien :".$param_car[$vin]["maintenanceDistance"]; $rpt_li += 1;
    }
  }
}

// =====================================================
// Fonction de lecture des positions GPS d'une voiture
// =====================================================
function get_car_gps($vin, $ts_start, $ts_end)
{
  global $cars_dt_gps;
  
  // ouverture du fichier de log
  $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'.gpslog';
  $fcar = fopen($fn_car, "r");

  // lecture des donnees
  $line = 0;
  $cars_dt_gps["log"] = [];
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      list($gps_ts, $gps_lat, $gps_lon, $gps_head) = explode(",", $buffer);
      $gps_tsi = intval($gps_ts);
      if (($gps_tsi>=$ts_start) && ($gps_tsi<=$ts_end) && ($gps_lat != 0) && ($gps_lon != 0)) {
        $cars_dt_gps["log"][$line] = $buffer;
        $line = $line + 1;
      }
    }
  }
  fclose($fcar);
  // Ajoute les coordonnées du domicile pour utilisation par javascript
  $latitute=config::byKey("info::latitude");
  $longitude=config::byKey("info::longitude");
  $cars_dt_gps["home"] = $latitute.",".$longitude;
  log::add('peugeotcars', 'debug', 'Ajax:get_car_gps:nb_lines='.$line);
  return;
}


// ===========================================================
// Fourniture des informations sur le véhicule (selon son VIN)
// ===========================================================
function get_car_infos($vin)
{
  $session_peugeotcars = new peugeotcars_api_v2();
  $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'));
  $session_peugeotcars->pg_api_login1_2();   // Authentification
  // Section caractéristiques véhicule
  $ret = $session_peugeotcars->pg_ap_mym_user();
  log::add('peugeotcars','debug','get_car_infos:sucess='.$ret["sucess"]);
  $info["vin"] = $vin;
  $info["short_label"] = $ret["short_label"];
  $info["lcdv"] = $ret["lcdv"];
  $info["warranty_start_date"] = date("j-n-Y", intval($ret["warranty_start_date"]));
  $info["eligibility"] = $ret["eligibility"];
  $info["types"] = $ret["eligibility"][0];
  $liste_logiciel = $ret["eligibility"][1];
  $info["mileage_km"] = $ret["mileage_val"];
  $info["mileage_ts"] = date("j-n-Y \à G\hi", intval($ret["mileage_ts"]));
  
  // Section version logiciels
  //log::add('peugeotcars','debug','get_car_infos:liste_logiciel='.$liste_logiciel);
  if (strpos($liste_logiciel, "rcc") !== FALSE) {
    // recherche la version SW du log RCC
    $ret = $session_peugeotcars->pg_api_sw_updates($vin, "rcc-firmware");
    $info["rcc_type"]            = $ret["sw_type"];
    $info["rcc_current_ver"]     = $ret["sw_current_ver"];
    $info["rcc_available_ver"]   = $ret["sw_available_ver"];
    $info["rcc_available_date"]  = $ret["sw_available_date"];
    $info["rcc_available_size"]  = $ret["sw_available_size"];
    $info["rcc_available_UpURL"] = $ret["sw_available_UpURL"];
    $info["rcc_available_LiURL"] = $ret["sw_available_LiURL"];
  }
  return $info;
}

// ======================================================================
// Fourniture des informations de maintenance du véhicule (selon son VIN)
// ======================================================================
function get_car_maint($vin)
{
  $session_peugeotcars = new peugeotcars_api_v2();
  $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'));
  $session_peugeotcars->pg_api_login1_2();   // Authentification
  // Section caractéristiques véhicule
  $ret = $session_peugeotcars->pg_ap_mym_maintenance($vin);
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

  if (init('action') == 'UpdateDataBase') {
    log::add('peugeotcars', 'info', 'Ajax:UpdateDataBase');
    get_trip_files();
    $ret_json = json_encode ($report);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getTripData') {
    log::add('peugeotcars', 'info', 'Ajax:getTripData');
    $vin = init('eqLogic_id');
    $ts_start = init('param')[0];
    $ts_end   = init('param')[1];
    log::add('peugeotcars', 'debug', 'vin   :'.$vin);
    log::add('peugeotcars', 'debug', 'param0:'.$ts_start);
    log::add('peugeotcars', 'debug', 'param1:'.$ts_end);
    // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
    get_car_trips_gps($vin, intval ($ts_start), intval ($ts_end));
    $ret_json = json_encode ($cars_dt);
    ajax::success($ret_json);
    }

  else if (init('action') == 'getGpsData') {
    log::add('peugeotcars', 'info', 'Ajax:getGpsData');
    $vin = init('eqLogic_id');
    $ts_start = init('param')[0];
    $ts_end   = init('param')[1];
    log::add('peugeotcars', 'debug', 'vin   :'.$vin);
    log::add('peugeotcars', 'debug', 'param0:'.$ts_start);
    log::add('peugeotcars', 'debug', 'param1:'.$ts_end);
    // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
    get_car_gps($vin, intval ($ts_start), intval ($ts_end));
    $ret_json = json_encode ($cars_dt_gps);
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

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
