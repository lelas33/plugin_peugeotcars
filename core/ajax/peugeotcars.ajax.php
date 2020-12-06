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
  // Ajoute les coordonnées du domicile pour utilisation par javascript
  $latitute=config::byKey("info::latitude");
  $longitude=config::byKey("info::longitude");
  $cars_dt["home"] = $latitute.",".$longitude;

  //log::add('peugeotcars', 'debug', 'Ajax:get_car_trips:nb_lines'.$line);
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
    log::add('peugeotcars', 'debug', 'param0:'.$ts_start);
    log::add('peugeotcars', 'debug', 'param1:'.$ts_end);
    // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
    get_car_trips_gps($vin, intval ($ts_start), intval ($ts_end));
    $ret_json = json_encode ($cars_dt);
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
