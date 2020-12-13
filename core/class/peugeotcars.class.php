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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api2.class.php';

define("CARS_FILES_DIR", "/../../data/");

// 2 fichiers pour enregistrer les trajets en détails
// car_trips.log: liste des trajets
//  * TRIP_STS: Start Timestamp 
//  * TRIP_ETS: End Timestamp
//  * TRIP_DTS: Distance parcourue pendant le trajet
//  * TRIP_DTS: Energie consommée pendant le trajet (en % batterie)

// car_gps.log : liste des positions de la voiture
//  * PTS_TS: Timestamp
//  * PTS_LAT: Lattitude GPS
//  * PTS_LON: Longitude GPS
//  * PTS_HDN: Cap
//  * PTS_BATT: Niveau de la batterie en %
//  * PTS_MLG: Kilométrage courant
//  * PTS_KIN: Voiture en mouvement


// Classe principale du plugin
// ===========================
class peugeotcars extends eqLogic {
    /*     * *************************Attributs****************************** */
    /*     * ***********************Methode static*************************** */


//    public function postInsert()
//    {
//        $this->postUpdate();
//    }
    
    public function preSave() {
    }

    private function getListeDefaultCommandes()
    {
        return array( "kilometrage"          => array('Kilometrage',         'info',  'numeric', "kms", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "entretien_dist"       => array('Dist.Entretien',      'info',  'numeric', "kms", 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "entretien_jours"      => array('Jours.Entretien',     'info',  'numeric',   "j", 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_level"        => array('Niveau batterie',     'info',  'numeric',   "%", 1, "GENERIC_INFO",   'peugeotcars::battery_status_mmi', 'peugeotcars::battery_status_mmi'),
                      "battery_autonomy"     => array('Autonomie',           'info',  'numeric', "kms", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_voltage"      => array('Tension batterie',    'info',  'numeric',   "V", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_current"      => array('Courant batterie',    'info',  'numeric',   "A", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "gps_position"         => array('Position GPS',        'info',  'string',     "", 0, "GENERIC_INFO",   'peugeotcars::opensmap',   'peugeotcars::opensmap'),
                      "conn_level"           => array('Niveau connection',   'info',  'numeric',    "", 1, "GENERIC_INFO",   'peugeotcars::con_level',  'peugeotcars::con_level'),
                      "kinetic_moving"       => array('Voiture en mouvement','info',  'binary',     "", 1, "GENERIC_INFO",   'peugeotcars::veh_moving', 'peugeotcars::veh_moving'),
                      "record_period"        => array('Période enregistrement','info','numeric',    "", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_plugged"     => array('Prise connectée',     'info',  'binary',     "", 1, "GENERIC_INFO",   'peugeotcars::plugged', 'peugeotcars::plugged'),
                      "charging_status"      => array('Statut charge',       'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_remain_time" => array('Temps restant',       'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_rate"        => array('Vitesse chargement',  'info',  'numeric',"km/h", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_mode"        => array('Mode chargement',     'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "precond_status"       => array('Etat climatisation',  'info',  'binary',     "", 1, "GENERIC_INFO",   'peugeotcars::clim', 'peugeotcars::clim'),
                      "num_photo_sld"        => array('Change photo',        'action','slider',     "", 0, "GENERIC_ACTION", 'peugeotcars::img', 'peugeotcars::img'),
                      "num_photo"            => array('Numéro photo',        'info',  'numeric',    "", 0, "GENERIC_INFO",   'core::badge', 'core::badge') 
        );
    }

    // public function postSave() : Called after equipement saving
    // ==========================================================
    public function postSave()
    {
      // Login API
      $session_peugeotcars = new peugeotcars_api_v2();
      $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), NULL);
      $login_token = $session_peugeotcars->pg_api_login1_2();   // Authentification
      if ($login_token["status"] == "KO") {
        log::add('peugeotcars','error',"Erreur Login API PSA");
        return;  // Ce vehicule n'est pas connecte
      }
      $vin = $this->getlogicalId();
      $ret = $session_peugeotcars->pg_api_vehicles($vin);
      log::add('peugeotcars','info',"postSave: success=".$ret["success"]);
      if ($ret["success"] == "KO") {
        log::add('peugeotcars','info',"Ce vehicule n'est pas connecté: vin=".$vin);
        return;  // Ce vehicule n'est pas connecte
      }
      $nb_images = count($ret["pictures"]);

      // creation de la liste des commandes / infos
      foreach( $this->getListeDefaultCommandes() as $id => $data) {
        list($name, $type, $subtype, $unit, $hist, $generic_type, $template_dashboard, $template_mobile) = $data;
        $cmd = $this->getCmd(null, $id);
        if (! is_object($cmd)) {
          $cmd = new peugeotcarsCmd();
          $cmd->setName($name);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setType($type);
          if ($type == "info") {
            $cmd->setDisplay ("showStatsOndashboard",0);
            $cmd->setDisplay ("showStatsOnmobile",0);
          }
          $cmd->setSubType($subtype);
          $cmd->setUnite($unit);
          $cmd->setLogicalId($id);
          $cmd->setIsHistorized($hist);
          $cmd->setDisplay('generic_type', $generic_type);
          $cmd->setTemplate('dashboard', $template_dashboard);
          $cmd->setTemplate('mobile', $template_mobile);
          if ($id == "num_photo_sld") {
            $cmd->setConfiguration('minValue', 0);
            $cmd->setConfiguration('maxValue', $nb_images-1);
            $cmd->setConfiguration('listValue', 'VIN|'.$vin);
          }
          else if ($id == "num_photo") {
            $cmd->setIsVisible(0);
            $cmd->event(0);
          }
          else if ($id == "record_period") {
            $cmd->setIsVisible(0);
          }
          else if ($id == "gps_position") {
            // Création des parametres de suivi des trajets
            $cmd->setConfiguration('trip_start_ts', 0);
            $cmd->setConfiguration('trip_start_mileage',  0);
            $cmd->setConfiguration('trip_start_battlevel', 0);
            $cmd->setConfiguration('trip_in_progress', 0);
          }
          $cmd->save();
        }
        else {
          $cmd->setType($type);
          if ($type == "info") {
            $cmd->setDisplay ("showStatsOndashboard",0);
            $cmd->setDisplay ("showStatsOnmobile",0);
          }
          $cmd->setSubType($subtype);
          $cmd->setUnite($unit);
          $cmd->setIsHistorized($hist);
          $cmd->setDisplay('generic_type', $generic_type);
          if ($id == "num_photo_sld") {
            $cmd->setConfiguration('minValue', 0);
            $cmd->setConfiguration('maxValue', $nb_images-1);
            $cmd->setConfiguration('listValue', 'VIN|'.$vin);
            $cmd->setValue(0);  // init à image 0
          }
          else if ($id == "num_photo") {
            $cmd->event(0);
          }
          else if ($id == "gps_position") {
            // Création des parametres de suivi des trajets
            $cmd->setConfiguration('trip_start_ts', 0);
            $cmd->setConfiguration('trip_start_mileage',  0);
            $cmd->setConfiguration('trip_start_battlevel', 0);
            $cmd->setConfiguration('trip_in_progress', 0);
          }
          $cmd->save();
        }
      }

      // Couplage des commandes et info "num_photo_sld" et "num_photo"
      $cmd_act = $this->getCmd(null, 'num_photo_sld');
      $cmd_inf = $this->getCmd(null, 'num_photo');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }

      // ajout de la commande refresh data
      $refresh = $this->getCmd(null, 'refresh');
      if (!is_object($refresh)) {
        $refresh = new peugeotcarsCmd();
        $refresh->setName(__('Rafraichir', __FILE__));
      }
      $refresh->setEqLogic_id($this->getId());
      $refresh->setLogicalId('refresh');
      $refresh->setType('action');
      $refresh->setSubType('other');
      $refresh->save();
      log::add('peugeotcars','debug','postSave:Ajout ou Mise à jour véhicule:'.$vin);
      
      // Ajout des photos de la voiture dans le dossier "data/vin/" du plugin (recuperation du lien par l'API Peugeot)
      log::add('peugeotcars','info','postSave:Sauvegarde de '.$nb_images. " photos");
      // création du dossier des donnees du vehicule dans le repertoire data
      $vin_dir = dirname(__FILE__).CARS_FILES_DIR.$vin;
      if (!file_exists($vin_dir)) {
        mkdir($vin_dir, 0777);
      }
      // et téléchargement des fichiers
      for ($img=0; $img<$nb_images; $img++) {
        $visual_fn = $vin_dir.'/img'.$img.".png";
        $visual_url = $ret["pictures"][$img];
        if (file_put_contents($visual_fn, file_get_contents($visual_url))) { 
          log::add('peugeotcars','info','postSave:Visual='.$visual_fn.": Correctement téléchargé");
        }
        else { 
          log::add('peugeotcars','info','postSave:Visual='.$visual_fn.": Erreur téléchargement");
        }
        
      }
    }

    public function preRemove() {
    }

    // Fonction appelée au rythme de 1 mn (recupeartion des informations courantes de la voiture)
    // ==========================================================================================
    public static function pull() {
      log::add('husqvarna_map','debug','Funcion pull');
      if (config::byKey('account', 'peugeotcars') != "" || config::byKey('password', 'peugeotcars') != "" ) {
        log::add('peugeotcars','debug','Mise à jour périodique');
        foreach (self::byType('peugeotcars') as $eqLogic) {
          $eqLogic->periodic_state(0);
        }
      }
    }
    // Lecture des statut du vehicule connecté
    public function periodic_state($rfh) {
      // V1 : API Connected car V3
      $minute = intval(date("i"));
      $heure  = intval(date("G"));
      // Appel API pour le statut courant du vehicule
      $vin = $this->getlogicalId();
      $fn_car_gps   = dirname(__FILE__).CARS_FILES_DIR.$vin.'/gps.log';
      $fn_car_trips = dirname(__FILE__).CARS_FILES_DIR.$vin.'/trips.log';

      if ($this->getIsEnable()) {
        $cmd_record_period = $this->getCmd(null, "record_period");
        $record_period = $cmd_record_period->execCmd();
        if ($record_period == NULL)
          $record_period = 0;
        //log::add('peugeotcars','debug',"record_period:".$record_period);

        if ((($record_period == 0) && ($minute%5 == 0)) || ($record_period > 0) || ($rfh==1)) {
          // Login a l'API PSA
          $last_login_token = $cmd_record_period->getConfiguration('save_auth');
          if ((!isset($last_login_token)) || ($last_login_token == "") || ($rfh==1))
            $last_login_token = NULL;
          $session_peugeotcars = new peugeotcars_api_v2();
          $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), $last_login_token);
          if ($last_login_token == NULL) {
            $login_token = $session_peugeotcars->pg_api_login1_2();   // Authentification
            if ($login_token["status"] != "OK")
              log::add('peugeotcars','error',"Erreur Login API PSA");
            $cmd_record_period->setConfiguration ('save_auth', $login_token);
            $cmd_record_period->save();
            log::add('peugeotcars','debug',"Pas de session en cours => New login");
          }
          else if ($session_peugeotcars->state_login() == 0) {
            $login_token = $session_peugeotcars->pg_api_login1_2();   // Authentification
            if ($login_token["status"] != "OK")
              log::add('peugeotcars','error',"Erreur Login API PSA");
            $cmd_record_period->setConfiguration ('save_auth', $login_token);
            $cmd_record_period->save();
            log::add('peugeotcars','debug',"Session expirée => New login");
          }
          // Capture du statut du vehicule
          $ret = $session_peugeotcars->pg_api_vehicles($vin);
          if ($ret["success"] == "KO")
            log::add('peugeotcars','error',"Erreur Login API PSA");
          $ret = $session_peugeotcars->pg_api_vehicles_status();
          log::add('peugeotcars','debug',"MAJ statut du véhicule:".$vin);
          $cmd_mlg = $this->getCmd(null, "kilometrage");
          $mileage = $ret["gen_mileage"];
          $previous_mileage = $cmd_mlg->execCmd();
          $previous_ts = $cmd_mlg->getConfiguration('prev_ctime');
          $cmd_mlg->event($mileage);
          $cmd = $this->getCmd(null, "battery_level");
          $batt_level = $ret["batt_level"];
          $previous_batt_level = $cmd->execCmd();
          $cmd->event($batt_level);
          $cmd = $this->getCmd(null, "battery_autonomy");
          $batt_auto = $ret["batt_autonomy"];
          $cmd->event($batt_auto);
          $cmd = $this->getCmd(null, "battery_voltage");
          $batt_voltage = $ret["batt_voltage"];
          $cmd->event($batt_voltage);
          $cmd = $this->getCmd(null, "battery_current");
          $batt_current = $ret["batt_current"];
          $cmd->event($batt_current);
          $cmd_gps = $this->getCmd(null, "gps_position");
          // Etat courant du trajet
          $trip_start_ts       = $cmd_gps->getConfiguration('trip_start_ts');
          $trip_start_mileage  = $cmd_gps->getConfiguration('trip_start_mileage');
          $trip_start_battlevel= $cmd_gps->getConfiguration('trip_start_battlevel');
          $trip_in_progress    = $cmd_gps->getConfiguration('trip_in_progress');
          $gps_position = $ret["gps_lat"].",".$ret["gps_lon"].",".$ret["gps_head"];
          $previous_gps_position = $cmd_gps->execCmd();
          //log::add('peugeotcars','debug',"Refresh log previous_gps_position=".$previous_gps_position);
          $cmd_gps->event($gps_position);

          $cmd = $this->getCmd(null, "conn_level");
          $conn_level = $ret["conn_level"];
          $cmd->event($conn_level);            
          $cmd = $this->getCmd(null, "kinetic_moving");
          $kinetic_moving = intval($ret["kinetic_moving"],10);
          $cmd->event($kinetic_moving);
          // Analyse debut et fin de trajet
          $ctime = time();
          $trip_event = 0;
          if ($trip_in_progress == 0) {
            // Pas de trajet en cours
            if ($kinetic_moving == 1) {
              // debut de trajet
              $trip_start_ts       = $previous_ts;
              $trip_start_mileage  = $previous_mileage;
              $trip_start_battlevel= $previous_batt_level;
              $trip_in_progress    = 1;
              $trip_event = 1;
              $cmd_gps->setConfiguration('trip_start_ts', $trip_start_ts);
              $cmd_gps->setConfiguration('trip_start_mileage', $trip_start_mileage);
              $cmd_gps->setConfiguration('trip_start_battlevel', $trip_start_battlevel);
              $cmd_gps->setConfiguration('trip_in_progress', $trip_in_progress);
              $cmd_gps->save();
            }
          }
          else {
            // Un trajet est en cours
            if (($kinetic_moving == 0) && ($record_period == 1)) {
              // fin de trajet
              $trip_end_ts       = $ctime;
              $trip_end_mileage  = $mileage;
              $trip_end_battlevel= $batt_level;
              $trip_in_progress  = 0;
              $trip_event = 1;
              // enregistrement d'un trajet
              $trip_distance = $trip_end_mileage - $trip_start_mileage;
              $trip_batt_diff = $trip_start_battlevel - $trip_end_battlevel;
              $trip_log_dt = $trip_start_ts.",".$trip_end_ts.",".$trip_distance.",".$trip_batt_diff."\n";
              log::add('peugeotcars','debug',"Refresh->recording Trip_dt=".$trip_log_dt);
              file_put_contents($fn_car_trips, $trip_log_dt, FILE_APPEND | LOCK_EX);
              $cmd_gps->setConfiguration('trip_in_progress', $trip_in_progress);
              $cmd_gps->save();
            }
          }
          // Log position courante vers GPS log file
//          if (($gps_position !== $previous_gps_position) || ($trip_event == 1)) {
            $gps_log_dt = $ctime.",".$gps_position.",".$batt_level.",".$mileage.",".$kinetic_moving."\n";
            log::add('peugeotcars','debug',"Refresh->recording Gps_dt=".$gps_log_dt);
            file_put_contents($fn_car_gps, $gps_log_dt, FILE_APPEND | LOCK_EX);
            // enregistre le ts du point courant
            $cmd_mlg->setConfiguration('prev_ctime', $ctime);
            $cmd_mlg->save();
//          }
          
          // Si le vehicule est en mouvement, passage en record toute les minutes, et au moins pour 5 mn
          if ($kinetic_moving == 1) {
            $record_period = 5;                  
          }
          else {
            if ($record_period > 0) {
              $record_period = $record_period - 1;                  
            }
          }
          $cmd_record_period->event($record_period);
          // Chargement batterie
          $cmd = $this->getCmd(null, "charging_plugged");
          $charging_plugged = $ret["charging_plugged"];
          $cmd->event($charging_plugged);
          $cmd = $this->getCmd(null, "charging_status");
          $charging_status = $ret["charging_status"];
          $cmd->event($charging_status);
          $cmd = $this->getCmd(null, "charging_remain_time");
          $charging_remain_time = $ret["charging_remain_time"];
          $cmd->event($charging_remain_time);
          $cmd = $this->getCmd(null, "charging_rate");
          $charging_rate = $ret["charging_rate"];
          $cmd->event($charging_rate);
          $cmd = $this->getCmd(null, "charging_mode");
          $charging_mode = $ret["charging_mode"];
          $cmd->event($charging_mode);
          $cmd = $this->getCmd(null, "precond_status");
          $precond_status = ($ret["precond_status"] == "Enabled")?1:0;
          $cmd->event($precond_status);
          // A minuit, mise à jour maintenance
          if (($heure==0) && ($minute==0)) {
            $ret = $session_peugeotcars->pg_ap_mym_maintenance($vin);
            log::add('peugeotcars','info',"Mise à jour date maintenance");
            // jours jusqu'à la prochaine visite
            $nbj_ts = round ((intval($ret["visite1_ts"]) - time())/(24*3600));
            $cmd = $this->getCmd(null, "entretien_jours");
            $cmd->event($nbj_ts);
            // km jusqu'à la prochaine visite
            $dist = intval($ret["visite1_mileage"]) - intval($ret["mileage_km"]);
            $cmd = $this->getCmd(null, "entretien_dist");
            $cmd->event($dist);          
          }
        }
      }
    }
}

// Classe pour les commandes du plugin
// ===================================
class peugeotcarsCmd extends cmd 
{
    /*     * *************************Attributs****************************** */
    public function execute($_options = null) {
        if ($this->getLogicalId() == 'refresh') {
          log::add('peugeotcars','info',"Refresh data");
          if (config::byKey('account', 'peugeotcars') != "" || config::byKey('password', 'peugeotcars') != "" ) {
            foreach (eqLogic::byType('peugeotcars') as $eqLogic) {
              $eqLogic->periodic_state(1);
            }
          }
        }
        else if ($this->getLogicalId() == 'num_photo_sld') {
          $eqLogic = $this->getEqLogic();
          $cmd_ass = $eqLogic->getCmd(null, 'num_photo');
          if (is_object($cmd_ass)) {
            log::add('peugeotcars','info',"num_photo_sld:".$_options['slider']);
            $cmd_ass->event($_options['slider']);
          }

        }

        
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
?>
