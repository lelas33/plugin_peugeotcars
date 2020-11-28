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
require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api.class.php';
require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api2.class.php';

//define("GPS_LOG_FILE",   "/../../data/gps_log.txt");
define("CARS_FILES_DIR", "/../../data/");


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
        return array( "vin"                  => array('Vin',                 'info',  'string',     "", 0, "GENERIC_INFO",   'peugeotcars::car_img', 'peugeotcars::car_img', ''),
                      "kilometrage"          => array('Kilometrage',         'info',  'numeric', "kms", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_dist"       => array('Dist.Entretien',      'info',  'numeric', "kms", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_jours"      => array('Jours.Entretien',     'info',  'numeric',   "j", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "vehicule_connecte"    => array('Vehic.Connecté',      'info',  'binary',     "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "battery_level"        => array('Niveau batterie',     'info',  'numeric',   "%", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "battery_autonomy"     => array('Autonomie',           'info',  'numeric', "kms", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "battery_voltage"      => array('Tension batterie',    'info',  'numeric',   "V", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "battery_current"      => array('Courant batterie',    'info',  'numeric',   "A", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "gps_position"         => array('Position GPS',        'info',  'string',     "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "conn_level"           => array('Niveau connection',   'info',  'numeric',    "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "kinetic_moving"       => array('Voiture en mouvement','info',  'binary',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "record_period"        => array('Période enregistrement','info','numeric',    "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "charging_plugged"     => array('Prise connectée',     'info',  'binary',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "charging_status"      => array('Statut charge',       'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "charging_remain_time" => array('Temps restant',       'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "charging_rate"        => array('Vitesse chargement',  'info',  'numeric',"km/h", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "charging_mode"        => array('Mode chargement',     'info',  'string',     "", 1, "GENERIC_INFO",   'core::badge', 'core::badge', '') 
        );
    }

    // public function postSave() : Called after equipement saving
    // ==========================================================
    public function postSave()
    {
      // Login API
      $session_peugeotcars = new peugeotcars_api_v1();
      $session_peugeotcars->login(config::byKey('token', 'peugeotcars'));
      foreach( $this->getListeDefaultCommandes() as $id => $data) {
        list($name, $type, $subtype, $unit, $hist, $generic_type, $template_dashboard, $template_mobile, $listValue) = $data;
        $cmd = $this->getCmd(null, $id);
        if ( ! is_object($cmd) ) {
          $cmd = new peugeotcarsCmd();
          $cmd->setName($name);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setType($type);
          $cmd->setSubType($subtype);
          $cmd->setUnite($unit);
          $cmd->setLogicalId($id);
          if ($id == "vin") {
            // Mise à jour du champ VIN a partir du logicalId
            $vin = $this->getlogicalId();
            log::add('peugeotcars','debug','postSave:add vin:'.$vin);
            $cmd->event($vin);
          }
          if ($listValue != "") {
            $cmd->setConfiguration('listValue', $listValue);
          }
          $cmd->setIsHistorized($hist);
          $cmd->setDisplay('generic_type', $generic_type);
          $cmd->setTemplate('dashboard', $template_dashboard);
          $cmd->setTemplate('mobile', $template_mobile);
          $cmd->save();
          // vérification de la capacité véhicule connecté
          if ($id == "vehicule_connecte") {
            $ret = $session_peugeotcars->pg_api_me_vehicules($vin);
            if ($ret["sucess"] == "OK") {
              $cmd->event(TRUE);
            }
            else {
              $cmd->event(FALSE);
              // Ne pas tenir compte des commandes & infos suivantes qui sont liees aux vehicule connectes
              break;
            }
          }
        }
        else {
          $cmd->setType($type);
          $cmd->setSubType($subtype);
          $cmd->setUnite($unit);
          $cmd->setIsHistorized($hist);
          $cmd->setDisplay('generic_type', $generic_type);
          if ($id == "vin") {
            // Mise à jour du champ VIN a partir du logicalId
            $vin = $this->getlogicalId();
            log::add('peugeotcars','debug','postSave:update vin:'.$vin);
            $cmd->event($vin);
          }
          if ($listValue != "") {
              $cmd->setConfiguration('listValue', $listValue);
          }
          $cmd->save();
          // vérification de la capacité véhicule connecté
          if ($id == "vehicule_connecte") {
            $ret = $session_peugeotcars->pg_api_me_vehicules($vin);
            if ($ret["sucess"] == "OK") {
              $cmd->event(TRUE);
            }
            else {
              $cmd->event(FALSE);
              // Ne pas tenir compte des commandes & infos suivantes qui sont liees aux vehicule connectes
              break;
            }
          }
        }
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
      
      // Ajout de la photo de la voiture dans le dossier "ressources" du plugin (recuperation du lien par l'API Peugeot)
      $ret = $session_peugeotcars->pg_api_car_vehicule($vin);
      log::add('peugeotcars','debug','postSave:Visual='.$ret["visual"]);
      $visual_url = $ret["visual"];
      $visual_fn = dirname(__FILE__).'/../../ressources/'.$vin.'.png';
      // et téléchargement du fichier
      if (file_put_contents($visual_fn, file_get_contents($visual_url))) { 
        log::add('peugeotcars','debug','postSave:Visual='.$visual_fn.": Correctement téléchargé");
      }
      else { 
        log::add('peugeotcars','debug','postSave:Visual='.$visual_fn.": Erreur téléchargement");
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
          $eqLogic->periodic_state();
        }
      }
    }
    // Lecture des statut du vehicule connecté
    public function periodic_state() {
      // V1 : API Connected car V3
      $minute = intval(date("i"));
      // Appel API pour le statut courant du vehicule
      $vin = $this->getlogicalId();
      $veh_con_cmd = $this->getCmd(null, "vehicule_connecte");
      $veh_con = $veh_con_cmd->execCmd();
      $fn_car = dirname(__FILE__).CARS_FILES_DIR.$vin.'.gpslog';

      if (($veh_con == TRUE) && ($this->getIsEnable())) {
        $cmd_record_period = $this->getCmd(null, "record_period");
        $record_period = $cmd_record_period->execCmd();
        if ($record_period == NULL)
          $record_period = 0;
        //log::add('peugeotcars','debug',"record_period:".$record_period);

        if ((($record_period == 0) && ($minute%5 == 0)) || ($record_period > 0)) {
          // Login a l'API PSA
          $last_login_token = $cmd_record_period->getConfiguration('save_auth');
          $session_peugeotcars = new peugeotcars_api_v2();
          $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), $last_login_token);
          if ($last_login_token == NULL) {
            $login_token = $session_peugeotcars->pg_api_login1_2();   // Authentification
            $cmd_record_period->setConfiguration ('save_auth', $login_token);
            $cmd_record_period->save();
            log::add('peugeotcars','debug',"Pas de session en cours => New login");
          }
          else if ($session_peugeotcars->state_login() == 0) {
            $login_token = $session_peugeotcars->pg_api_login1_2();   // Authentification
            $cmd_record_period->setConfiguration ('save_auth', $login_token);
            $cmd_record_period->save();
            log::add('peugeotcars','debug',"Session expirée => New login");
          }
          // Capture du statut du vehicule
          $session_peugeotcars->pg_api_vehicles();
          $ret = $session_peugeotcars->pg_api_vehicles_status();
          log::add('peugeotcars','debug',"MAJ statut du véhicule:".$vin);
          $cmd = $this->getCmd(null, "kilometrage");
          $kms    = $ret["gen_mileage"];
          $cmd->event($kms);
          $cmd = $this->getCmd(null, "battery_level");
          $batt_level = $ret["batt_level"];
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
          $cmd = $this->getCmd(null, "gps_position");
          $gps_position = $ret["gps_lat"].",".$ret["gps_lon"].",".$ret["gps_head"];
          $current_gps_position = $cmd->execCmd();
          //log::add('peugeotcars','debug',"Refresh log current_gps_position=".$current_gps_position);
          $cmd->event($gps_position);
          if ($gps_position !== $current_gps_position) {
            $gps_log_dt = time().",".$gps_position.",".$batt_level.",".$kms."\n";
            log::add('peugeotcars','debug',"Refresh log recording Gps_dt=".$gps_log_dt);
            file_put_contents($fn_car, $gps_log_dt, FILE_APPEND | LOCK_EX);
          }
          $cmd = $this->getCmd(null, "conn_level");
          $conn_level = $ret["conn_level"];
          $cmd->event($conn_level);            
          $cmd = $this->getCmd(null, "kinetic_moving");
          $kinetic_moving = $ret["kinetic_moving"];
          $cmd->event($kinetic_moving);
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
        if ( $this->getLogicalId() == 'refresh') {
          log::add('peugeotcars','info',"Refresh data");
          peugeotcars::pull();
        }

        
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
?>
