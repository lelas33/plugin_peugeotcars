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

define("MYP_FILES_DIR",  "/../../data/MyPeugeot/");
define("CARS_FILES_DIR", "/../../data/");

global $cars_dt;
global $report;

// =====================================================
// Fonction de lecture de tous les trajets d'une voiture
// =====================================================
function get_car_trips($vin, $ts_start, $ts_end)
{
  global $cars_dt;
  
  // ouverture du fichier de log
  $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'.db';
  $fcar = fopen($fn_car, "r");

  // lecture des donnees
  $line = 0;
  $cars_dt["log"] = [];
  if ($fcar) {
    while (($buffer = fgets($fcar, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      list($tr_id, $tr_st, $tr_et, $tr_sm, $tr_em, $tr_ds, $tr_mdy, $tr_mds) = explode(",", $buffer);
      $tsi_s = intval($tr_st);
      $tsi_e = intval($tr_et);
      if (($tsi_s>=$ts_start) && ($tsi_e<=$ts_end)) {
        $cars_dt["log"][$line] = $buffer;
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
        if (trip_is_in_base($car_vin, $myp[$car]["trips"][$trip]["id"], $myp[$car]["trips"][$trip]["startDateTime"]) === 0) {
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
            $param_car[$car_vin]["last_trip"] = intval($myp[$car]["trips"][$trip]["startDateTime"]);
            $param_car[$car_vin]["endMileage"] = intval($myp[$car]["trips"][$trip]["endMileage"]);
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
      $cmd->event($param_car[$vin]["endMileage"]);
      $cmd = $eq->getCmd(null, "entretien_jours");
      $cmd->event($param_car[$vin]["maintenanceDays"]);
      $cmd = $eq->getCmd(null, "entretien_dist");
      $cmd->event($param_car[$vin]["maintenanceDistance"]);
      $report["log"][$rpt_li] = "Mise à jour du kilomètrage: ".$param_car[$vin]["endMileage"]; $rpt_li += 1;
      $report["log"][$rpt_li] = "Mise à jour du délai jusqu'à l'entretien: ".$param_car[$vin]["maintenanceDays"]; $rpt_li += 1;
      $report["log"][$rpt_li] = "Mise à jour du nombre de kilomètre jusqu'à l'entretien :".$param_car[$vin]["maintenanceDistance"]; $rpt_li += 1;
    }
  }

  
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
    get_car_trips($vin, intval ($ts_start), intval ($ts_end));
    $ret_json = json_encode ($cars_dt);
    ajax::success($ret_json);
    }


    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
