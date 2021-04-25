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
require_once dirname(__FILE__) . '/../../3rdparty/peugeotcars_api3.class.php';
require_once dirname(__FILE__) . '/../php/mqtt_com.php';

define("CARS_FILES_DIR_CL", "/../../data/");


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
//  * PTS_ALT: Altitude
//  * PTS_BATT: Niveau de la batterie en %
//  * PTS_MLG: Kilométrage courant
//  * PTS_KIN: Voiture en mouvement


// Classe principale du plugin
// ===========================
class peugeotcars extends eqLogic {
    /*     * *************************Attributs****************************** */
    /*     * ***********************Methode static*************************** */

	
    // Gestion de l'installation des dépendances du plugin
    // ===================================================
    public static function dependancy_info() {
      $return = array();
      $return['log'] = 'peugeotcars_update';
      $return['progress_file'] = jeedom::getTmpFolder('peugeotcars') . '/dependance';
      $return['state'] = 'nok';
      // verification de quelques librairies python necessaires
      $cmd = "python3 -m pip list | grep paho-mqtt";
      exec($cmd, $output1, $return_var);
      $cmd = "python3 -m pip list | grep cryptography";
      exec($cmd, $output2, $return_var);
      $cmd = "python3 -m pip list | grep oauth2-client";
      exec($cmd, $output3, $return_var);
      //log::add('peugeotcars', 'info', 'dependancy_info:'.$output1[0]);
      //log::add('peugeotcars', 'info', 'dependancy_info:'.$output2[0]);
      //log::add('peugeotcars', 'info', 'dependancy_info:'.$output3[0]);
      if (($output1[0] != "") && ($output2[0] != "") && ($output3[0] != "")) {
        $return['state'] = 'ok';
      }
      return $return;
    }
    
    public static function dependancy_install() {
      log::remove(__CLASS__ . '_update');
      $add_params = " " . config::byKey('account', 'peugeotcars') . " " . config::byKey('password', 'peugeotcars');
      log::add('peugeotcars', 'info', 'dependancy_install:'.$add_params);
      return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('peugeotcars') . '/dependance' . $add_params, 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    // Gestion du daemon (interface vers serveur MQTT PSA)
    // ===================================================
    public static function deamon_info() {
        $return = array();
				$return['state'] = count(system::ps('3rdparty/psa_jeedom_daemon/jeedom_gateway.py')) > 0 ? 'ok' : 'nok';
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start($_debug = false) {
				self::deamon_stop();
        log::add('peugeotcars', 'info', 'Starting daemon');
        $param = config::byKey('account', 'peugeotcars') . ',' . config::byKey('password', 'peugeotcars') . ',' . config::byKey('code_sms', 'peugeotcars') . ',' . config::byKey('code_pin', 'peugeotcars');
        $param = base64_encode ($param);

				// $cmd  = 'sudo /usr/bin/python3 ' . dirname(__FILE__) . '/../../3rdparty/psa_jeedom/jeedom_gateway.py';
        // $cmd .= ' -m ' . $param;
        // $cmd .= ' -b ' . dirname(__FILE__) . '/../../3rdparty/psa_jeedom';
				// $cmd .= ' >> ' . log::getPathToLog('peugeotcars') . ' 2>&1 &';

				$cmd  = 'sudo /usr/bin/python3 ' . dirname(__FILE__) . '/../../3rdparty/psa_jeedom_daemon/jeedom_gateway.py';
        $cmd .= ' -m ' . $param;
        $cmd .= ' -b ' . dirname(__FILE__) . '/../../3rdparty/psa_jeedom_daemon';
				$cmd .= ' >> ' . log::getPathToLog('peugeotcars') . ' 2>&1 &';

        log::add('peugeotcars', 'info', $cmd);
				shell_exec($cmd);
        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('peugeotcars', 'error', 'Unable to start daemon');
            return false;
        }
    }

    public static function deamon_stop() {
        log::add('peugeotcars', 'info', 'Stopping daemon');
				if (count(system::ps('3rdparty/psa_jeedom_daemon/jeedom_gateway.py')) > 0) {
					system::kill('3rdparty/psa_jeedom_daemon/jeedom_gateway.py', false);
				}
    }

//    public function postInsert()
//    {
//        $this->postUpdate();
//    }
    
    public function preSave() {
    }

    private function getListeDefaultCommandes()
    {
        return array( "veh_type"             => array('Type véhicule',       'info',  'string',     "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "kilometrage"          => array('Kilometrage',         'info',  'numeric',  "km", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "entretien_dist"       => array('Distance',            'info',  'numeric',  "km", 0, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "entretien_jours"      => array('Nb.Jours',            'info',  'numeric',   "j", 0, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_level"        => array('Niveau batterie',     'info',  'numeric',   "%", 1, 1, "GENERIC_INFO",   'peugeotcars::battery_status_mmi', 'peugeotcars::battery_status_mmi'),
                      "battery_autonomy"     => array('Autonomie',           'info',  'numeric',  "km", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_voltage"      => array('Tension',             'info',  'numeric',   "V", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "battery_current"      => array('Courant',             'info',  'numeric',   "A", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "gps_position"         => array('Position GPS',        'info',  'string',     "", 0, 1, "GENERIC_INFO",   'peugeotcars::opensmap',   'peugeotcars::opensmap'),
                      "gps_position_lat"     => array('Position GPS Lat.',   'info',  'numeric',    "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "gps_position_lon"     => array('Position GPS Lon.',   'info',  'numeric',    "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "gps_position_alt"     => array('Position GPS Alt.',   'info',  'numeric',    "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "gps_dist_home"        => array('Distance maison',     'info',  'numeric',  "km", 1, 1, "GENERIC_INFO",   'core::line', 'core::line'),
                      "conn_level"           => array('Niveau connection',   'info',  'numeric',    "", 1, 1, "GENERIC_INFO",   'peugeotcars::con_level',  'peugeotcars::con_level'),
                      "kinetic_moving"       => array('Voiture en mouvement','info',  'binary',     "", 1, 1, "GENERIC_INFO",   'peugeotcars::veh_moving', 'peugeotcars::veh_moving'),
                      "record_period"        => array('Période enregistrement','info','numeric',    "", 1, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_plugged"     => array('Prise de charge',     'info',  'string',     "", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_imdel_cmd"   => array('Immédiat/Différé',    'action','other',      "", 0, 1, "GENERIC_ACTION", 'peugeotcars::imm_diff', 'peugeotcars::imm_diff'),
                      "charging_imdel_val"   => array('Immédiat/Différé_',   'info',  'binary',     "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_del_hour_cmd"=> array('Set Heure départ',    'action','other',      "", 0, 1, "GENERIC_ACTION", 'peugeotcars::hour', 'peugeotcars::hour'),
                      "charging_del_hour"    => array('Heure départ',        'info',  'string',     "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_status"      => array('Statut charge',       'info',  'string',     "", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_remain_time" => array('Temps de charge',     'info',  'string',     "", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_end_time"    => array('Fin de charge',       'info',  'string',     "", 0, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_rate"        => array('Vitesse de charge',   'info',  'numeric',"km/h", 1, 1, "GENERIC_INFO",   'core::line', 'core::line'),
                      "charging_mode"        => array('Mode de charge',      'info',  'string',     "", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_batmax_cmd"  => array('Charge batterie maxi','action','slider',     "", 0, 1, "GENERIC_ACTION", 'peugeotcars::SliderButton', 'peugeotcars::SliderButton'),
                      "charging_batmax_val"  => array('Charge batterie maxi_','info', 'numeric',   "%", 1, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "charging_state"       => array('Etat de la charge',   'info',  'numeric',    "", 1, 1, "GENERIC_INFO",   'peugeotcars::charging_state', 'peugeotcars::charging_state'),
                      "precond_status"       => array('Etat climatisation',  'info',  'binary',     "", 1, 1, "GENERIC_INFO",   'peugeotcars::clim', 'peugeotcars::clim'),
                      "precond_start"        => array('Start',               'action','other',      "", 0, 1, "GENERIC_ACTION", 'core::badge', 'core::badge'),
                      "precond_stop"         => array('Stop',                'action','other',      "", 0, 1, "GENERIC_ACTION", 'core::badge', 'core::badge'),
                      "num_photo_sld"        => array('Change photo',        'action','slider',     "", 0, 1, "GENERIC_ACTION", 'peugeotcars::img', 'peugeotcars::img'),
                      "num_photo"            => array('Numéro photo',        'info',  'numeric',    "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "test_mqtt1"           => array('Test MQTT1',          'action','other',      "", 0, 0, "GENERIC_ACTION", 'core::badge', 'core::badge'),
                      "test_mqtt2"           => array('Test MQTT2',          'action','other',      "", 0, 0, "GENERIC_ACTION", 'core::badge', 'core::badge'),
                      "info_libre1"          => array('Libre1',              'info',  'string',     "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "info_libre2"          => array('Libre2',              'info',  'string',     "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "reason"               => array('reason',              'info',  'numeric',    "", 1, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "sev_state"            => array('sev_state',           'info',  'numeric',    "", 1, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "num_photo"            => array('Numéro photo',        'info',  'numeric',    "", 0, 0, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      // Informations complémentaires pour vehicule hybride                               
                      "fuel_level"           => array('Niveau carburant',    'info',  'numeric',   "%", 1, 1, "GENERIC_INFO",   'peugeotcars::battery_status_mmi', 'peugeotcars::battery_status_mmi'),
                      "fuel_autonomy"        => array('Autonomie carburant', 'info',  'numeric',  "km", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge'),
                      "fuel_ready"           => array('Véhicule Actif',      'info',  'binary',     "", 1, 1, "GENERIC_INFO",   'core::badge', 'core::badge')
        );
    }

    // public function postSave() : Called after equipement saving
    // ==========================================================
    public function postSave()
    {
      // filtrage premier passage
      $vin = $this->getlogicalId();
      if ($vin == "")
        return;

      // Login API
      $session_peugeotcars = new peugeotcars_api3();
      $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), NULL);
      $login_token = $session_peugeotcars->pg_api_login();   // Authentification
      if ($login_token["status"] == "KO") {
        log::add('peugeotcars','error',"Erreur Login API PSA");
        return;  // Erreur de login API PSA
      }
      $ret = $session_peugeotcars->pg_api_vehicles($vin);
      log::add('peugeotcars','debug',"postSave: success=".$ret["success"]);
      if ($ret["success"] == "KO") {
        log::add('peugeotcars','error',"Ce vehicule n'est pas connecté: vin=".$vin);
        return;  // Ce vehicule n'est pas connecte
      }
      $nb_images = count($ret["pictures"]);
      // Statut du véhicule pour mise à jour du type ("Electric" / "Hybrid")
      $ret_sts = $session_peugeotcars->pg_api_vehicles_status();
      $veh_type = $ret_sts["service_type"];

      // creation de la liste des commandes / infos
      foreach( $this->getListeDefaultCommandes() as $id => $data) {
        list($name, $type, $subtype, $unit, $hist, $visible, $generic_type, $template_dashboard, $template_mobile) = $data;
        $cmd = $this->getCmd(null, $id);
        if (! is_object($cmd)) {
          // New CMD
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
          $cmd->setIsVisible($visible);
          $cmd->setDisplay('generic_type', $generic_type);
          $cmd->setTemplate('dashboard', $template_dashboard);
          $cmd->setTemplate('mobile', $template_mobile);
          if ($id == "veh_type") {
            $cmd->save();
            $cmd->event($veh_type);
            log::add('peugeotcars','debug',"postSave: service_type=".$veh_type);
          }
          else if ($id == "num_photo_sld") {
            $cmd->setConfiguration('minValue', 0);
            $cmd->setConfiguration('maxValue', $nb_images-1);
            $cmd->setConfiguration('listValue', 'VIN|'.$vin);
            $cmd->save();
          }
          else if ($id == "num_photo") {
            $cmd->save();
            $cmd->event(0);
          }
          else if ($id == "charging_del_hour") {
            $cmd->save();
            $cmd->event("22:00");
          }
          else if ($id == "charging_batmax_cmd") {
            $cmd->setConfiguration('minValue', 40);
            $cmd->setConfiguration('maxValue', 100);
            $cmd->setConfiguration('step', 5);     // En pratique, géré par le widget en JS
            $cmd->save();
          }
          else if ($id == "charging_batmax_val") {
            $cmd->save();
            $cmd->event("100");
          }
          else if ($id == "gps_position") {
            // Création des parametres de suivi des trajets
            $cmd->setConfiguration('trip_start_ts', 0);
            $cmd->setConfiguration('trip_start_mileage',  0);
            $cmd->setConfiguration('trip_start_battlevel', 0);
            $cmd->setConfiguration('trip_in_progress', 0);
            $cmd->save();
          }
          else if (substr($id, 0, 5) == "fuel_") {
            // Ajoute les commandes "fuel_xxx" pour les véhicules hybrides
            if ($veh_type == "hybrid") {
              $cmd->save();
            }
          }
          else {
            $cmd->save();
          }
        }
        else {
          // Upadate CMD
          $cmd->setType($type);
          if ($type == "info") {
            $cmd->setDisplay ("showStatsOndashboard",0);
            $cmd->setDisplay ("showStatsOnmobile",0);
          }
          $cmd->setSubType($subtype);
          $cmd->setUnite($unit);
          // $cmd->setIsHistorized($hist);
          // $cmd->setIsVisible($visible);
          $cmd->setDisplay('generic_type', $generic_type);
          // $cmd->setTemplate('dashboard', $template_dashboard);
          // $cmd->setTemplate('mobile', $template_mobile);
          if ($id == "veh_type") {
            $cmd->save();
            $cmd->event($ret_sts["service_type"]);
            log::add('peugeotcars','debug',"postSave: service_type=".$ret_sts["service_type"]);
          }
          else if ($id == "num_photo_sld") {
            $cmd->setConfiguration('minValue', 0);
            $cmd->setConfiguration('maxValue', $nb_images-1);
            $cmd->setConfiguration('listValue', 'VIN|'.$vin);
            $cmd->setValue(0);  // init à image 0
            $cmd->save();
          }
          else if ($id == "num_photo") {
            $cmd->save();
            $cmd->event(0);
          }
          else if ($id == "gps_position") {
            // Création des parametres de suivi des trajets
            $cmd->setConfiguration('trip_start_ts', 0);
            $cmd->setConfiguration('trip_start_mileage',  0);
            $cmd->setConfiguration('trip_start_battlevel', 0);
            $cmd->setConfiguration('trip_in_progress', 0);
            $cmd->save();
          }
          else {
            $cmd->save();
          }
        }
      }

      // Couplage des commandes et info "num_photo_sld" et "num_photo"
      $cmd_act = $this->getCmd(null, 'num_photo_sld');
      $cmd_inf = $this->getCmd(null, 'num_photo');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }
      // Couplage des commandes et info "charging_imdel_cmd" et "charging_imdel_val"
      $cmd_act = $this->getCmd(null, 'charging_imdel_cmd');
      $cmd_inf = $this->getCmd(null, 'charging_imdel_val');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }
      // Couplage des commandes et info "charging_del_hour_cmd" et "charging_del_hour"
      $cmd_act = $this->getCmd(null, 'charging_del_hour_cmd');
      $cmd_inf = $this->getCmd(null, 'charging_del_hour');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }

      // Couplage des commandes et info "charging_batmax_cmd" et "charging_batmax_val"
      $cmd_act = $this->getCmd(null, 'charging_batmax_cmd');
      $cmd_inf = $this->getCmd(null, 'charging_batmax_val');
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
      $vin_dir = dirname(__FILE__).CARS_FILES_DIR_CL.$vin;
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

    // Fonction appelée au rythme de 1 mn (recuperation des informations courantes de la voiture)
    // ==========================================================================================
    public static function pull() {
      if (config::byKey('account', 'peugeotcars') != "" || config::byKey('password', 'peugeotcars') != "" ) {
        foreach (self::byType('peugeotcars') as $eqLogic) {
          $eqLogic->periodic_state(0);
        }
      }
    }
    // Lecture des statuts du vehicule connecté
    public function periodic_state($rfh) {
      if ($rfh == 1)
        log::add('peugeotcars','debug','Mise à jour manuelle');
      else
        log::add('peugeotcars','debug','Mise à jour périodique');

      // V1 : API Connected car V3
      $minute = intval(date("i"));
      $heure  = intval(date("G"));
      // Appel API pour le statut courant du vehicule
      $vin = $this->getlogicalId();
      $fn_car_gps   = dirname(__FILE__).CARS_FILES_DIR_CL.$vin.'/gps.log';
      $fn_car_trips = dirname(__FILE__).CARS_FILES_DIR_CL.$vin.'/trips.log';
      $alternate_trips = $this->getConfiguration("alternate_trips");

      if ($this->getIsEnable()) {
        $cmd_record_period = $this->getCmd(null, "record_period");
        $record_period = $cmd_record_period->execCmd();
        if ($record_period == NULL)
          $record_period = 0;
        //log::add('peugeotcars','debug',"record_period:".$record_period);

        // Toutes les 10 mn => Réveil de la voiture si elle est en charge pour avoir une remontée des infos de charge courante
        if (($minute%10 == 8) && ($rfh == 0)) {
          $cmd = $this->getCmd(null, "charging_status");
          $charging_status = $cmd->execCmd();
          if (strtolower($charging_status) == "inprogress") {
            $this->mqtt_submit(CMD_WAKEUP);
          }
        }
        // Toutes les 5 mn en mode trajet alternatifs, recuperation des infos d'etat par le serveur MQTT
        if (($minute%5 == 4) && ($alternate_trips == 1)) {
          $mqtt_ret = $this->mqtt_submit(CMD_GET_STATE);
          // $debug_export = var_export($mqtt_ret, true);
          // log::add('peugeotcars', 'info', "mqtt_return: debug:".$debug_export);
        }
        // Toutes les 5 mn => Mise à jour des informations de la voiture
        if ((($record_period == 0) && ($minute%5 == 0)) || ($record_period > 0) || ($rfh == 1)) {
          // Login a l'API PSA
          $last_login_token = $cmd_record_period->getConfiguration('save_auth');
          if ((!isset($last_login_token)) || ($last_login_token == "") || ($rfh==1))
            $last_login_token = NULL;
          $session_peugeotcars = new peugeotcars_api3();
          $session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'), $last_login_token);
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
          $ret = $session_peugeotcars->pg_api_vehicles_status();
          
          // Capture des infos complementaires depuis le serveur MQTT
          if ($alternate_trips == 1) {
            $mqtt_ret = $this->mqtt_submit(CMD_GET_STATE_RD);
            $alt_signal_quality = intval($mqtt_ret->resp_data->signal_quality)*2;
            $alt_reason         = intval($mqtt_ret->resp_data->reason);
            $alt_sev_state      = intval($mqtt_ret->resp_data->sev_state);
            log::add('peugeotcars', 'debug', "mqtt_return: signal_quality:".$alt_signal_quality." / reason:".$alt_reason." / sev_state:".$alt_sev_state);
          }

          // Traitement des informations retournees
          $batt_nominal_voltage = $this->getConfiguration("batt_nominal_voltage");
          $veh_type = $ret["service_type"];
          log::add('peugeotcars','debug',"MAJ statut du véhicule:".$vin);
          $cmd_mlg = $this->getCmd(null, "kilometrage");
          $mileage = round(floatval($ret["gen_mileage"]), 1);
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
          $batt_voltage = round(($batt_nominal_voltage * $ret["batt_voltage"]) / 100);
          $cmd->event($batt_voltage);
          $cmd = $this->getCmd(null, "battery_current");
          $batt_current = $ret["batt_current"];
          $cmd->event($batt_current);
          // infos complementaires pour vehicule hybride
          if ($veh_type == "hybrid") {
            $cmd = $this->getCmd(null, "fuel_level");
            $cmd_ready = $this->getCmd(null, "fuel_ready");
            $fuel_level = intval($ret["fuel_level"]);
            if ($fuel_level != 0) {
              $cmd->event($fuel_level);
              $cmd_ready->event(true);
            }
            else {
              $cmd_ready->event(false);
            }
            $cmd = $this->getCmd(null, "fuel_autonomy");
            $fuel_auto = $ret["fuel_autonomy"];
            $cmd->event($fuel_auto);
          }
          // Etat courant du trajet
          $cmd_gps = $this->getCmd(null, "gps_position");
          $trip_start_ts       = $cmd_gps->getConfiguration('trip_start_ts');
          $trip_start_mileage  = $cmd_gps->getConfiguration('trip_start_mileage');
          $trip_start_battlevel= $cmd_gps->getConfiguration('trip_start_battlevel');
          $trip_in_progress    = $cmd_gps->getConfiguration('trip_in_progress');
          if (($ret["gps_lat"] == 0) && ($ret["gps_lon"] == 0))
            $gps_pts_ok = false; // points GPS non valide
          else
            $gps_pts_ok = true;
          if ($gps_pts_ok == true) {
            $gps_position = $ret["gps_lat"].",".$ret["gps_lon"].",".$ret["gps_alt"];
            //$previous_gps_position = $cmd_gps->execCmd();
            //log::add('peugeotcars','debug',"Refresh log previous_gps_position=".$previous_gps_position);
            $cmd_gps->event($gps_position.",".$vin);
            $cmd_gpslat = $this->getCmd(null, "gps_position_lat");
            $cmd_gpslat->event(floatval($ret["gps_lat"]));
            $cmd_gpslon = $this->getCmd(null, "gps_position_lon");
            $cmd_gpslon->event(floatval($ret["gps_lon"]));
            $cmd_gpsalt = $this->getCmd(null, "gps_position_alt");
            $cmd_gpsalt->event(floatval($ret["gps_alt"]));
            // Calcul distance maison
            $lat_home = deg2rad(floatval(config::byKey("info::latitude")));
            $lon_home = deg2rad(floatval(config::byKey("info::longitude")));
            $lat_veh = deg2rad(floatval($ret["gps_lat"]));
            $lon_veh = deg2rad(floatval($ret["gps_lon"]));
            $dist_home = 6371.01 * acos(sin($lat_home)*sin($lat_veh) + cos($lat_home)* cos($lat_veh)*cos($lon_home - $lon_veh)); // calcul de la distance
            $dist_home = number_format($dist_home, 3, '.', '');//formatage 3 décimales
            $cmd_dis_home = $this->getCmd(null, "gps_dist_home");
            $cmd_dis_home->event($dist_home);
          }
          // Autres infos
          $cmd = $this->getCmd(null, "conn_level");
          $conn_level = ($alternate_trips == 0) ? $ret["conn_level"] : $alt_signal_quality;
          $cmd->event($conn_level);            
          $cmd = $this->getCmd(null, "kinetic_moving");
          $kinetic_moving = intval($ret["kinetic_moving"],10);
          $cmd->event($kinetic_moving);
          if ($alternate_trips == 1) {
            $cmd = $this->getCmd(null, "reason");
            $cmd->event($alt_reason);
            $cmd = $this->getCmd(null, "sev_state");
            $cmd->event($alt_sev_state);
          }
          
          // Analyse debut et fin de trajet
          $ctime = time();
          $trip_event = 0;
          if ($trip_in_progress == 0) {
            // Pas de trajet en cours
            if (($kinetic_moving == 1) && ($previous_mileage != 0) && ($previous_batt_level != 0)) {
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
              $trip_distance = round($trip_end_mileage - $trip_start_mileage, 1);
              $trip_batt_diff = $trip_start_battlevel - $trip_end_battlevel;
              $trip_log_dt = $trip_start_ts.",".$trip_end_ts.",".$trip_distance.",".$trip_batt_diff."\n";
              log::add('peugeotcars','info',"Refresh->recording Trip_dt=".$trip_log_dt);
              file_put_contents($fn_car_trips, $trip_log_dt, FILE_APPEND | LOCK_EX);
              $cmd_gps->setConfiguration('trip_in_progress', $trip_in_progress);
              $cmd_gps->save();
            }
          }
          // Log position courante vers GPS log file (pas si vehicule à l'arrêt "à la maison")
          if (($gps_pts_ok == true) && (($dist_home > 0.050) || ($kinetic_moving == 1))) {
            $gps_log_dt = $ctime.",".$gps_position.",".$batt_level.",".$mileage.",".$kinetic_moving."\n";
            log::add('peugeotcars','debug',"Refresh->recording Gps_dt=".$gps_log_dt);
            file_put_contents($fn_car_gps, $gps_log_dt, FILE_APPEND | LOCK_EX);
          }
          // enregistre le ts du point courant
          $cmd_mlg->setConfiguration('prev_ctime', $ctime);
          $cmd_mlg->save();
          
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
          $prev_charging_plugged = $cmd->execCmd();
          $charging_plugged = strval($ret["charging_plugged"]);
          $cmd->event($charging_plugged);
          $cmd = $this->getCmd(null, "charging_status");
          $charging_status = $ret["charging_status"];
          $cmd->event($charging_status);
          $cmd_rt = $this->getCmd(null, "charging_remain_time");
          $charging_remain_time = $ret["charging_remain_time"];
          $cmd_et = $this->getCmd(null, "charging_end_time");
          $charging_end_time = $ret["charging_end_time"];
          if ((strtolower($charging_status) != "inprogress") ||
             ((strtolower($charging_status) == "inprogress") && ($charging_remain_time != "--"))) {
            $cmd_rt->event($charging_remain_time);
            $cmd_et->event($charging_end_time);
          }
          $cmd = $this->getCmd(null, "charging_rate");
          $charging_rate = $ret["charging_rate"];
          $cmd->event($charging_rate);
          $cmd = $this->getCmd(null, "charging_mode");
          $charging_mode = $ret["charging_mode"];
          $cmd->event($charging_mode);
          $cmd = $this->getCmd(null, "precond_status");
          $precond_status = ($ret["precond_status"] == "Enabled")?1:0;
          //$cmd->event($precond_status);
          $this->checkAndUpdateCmd($cmd, $precond_status);
          // gestion de l'arret de la charge si demandée
          // ===========================================
          $cmd_batmax_val = $this->getCmd(null, 'charging_batmax_val');
          $batmax_val = intval($cmd_batmax_val->execCmd());
          if ((strtolower($charging_status) == "inprogress") && ($batmax_val < 100)) {
            if ($batt_level >= $batmax_val) {
              log::add('peugeotcars','info',"Interruption de la charge de la batterie. (Max level=".$batmax_val."% & Current level=".$batt_level."%)");
              $imm_diff = $this->cfg_charging(1,0);  // stoppe charge en passant en mode charge différée
            }
          }
          // gestion de l'etat courant de la charge
          // Etats possibles:
          //  * (0):off     => "Pas de charge en cours ou prévue". (prise débranchée)             => [couleur=gris fixe]
          //  * (1):pending => "Prise branchée, mode différé, et attente de l'heure de départ"    => [couleur=bleu fixe]
          //  * (2):charging=> "Charge en cours"                                                  => [couleur=vert clignotant]
          //  * (3):finished=> "Charge terminée"                                                  => [couleur=vert fixe]
          // Lorsque l'on détecte le branchement de la prise, on (re-)lance une configuration du mode immédiat/différé + heure de départ
          $cmd_cs = $this->getCmd(null, "charging_state");
          $charging_state = $cmd_cs->execCmd();
          
          if ($charging_state == NULL)
            $new_charging_state = 0;
          if ($charging_plugged == '0') {
            $new_charging_state = 0;
          }
          else if (($prev_charging_plugged == '0') && ($charging_plugged == '1')) {
            // detection du branchement de la prise => lancement configuration du mode immédiat/différé + heure de départ
            $imm_diff = $this->cfg_charging(0);
            if ($imm_diff == 0) {
              $new_charging_state = 1;  // si mode différé
              log::add('peugeotcars','info',"Branchement de la prise de charge détecté => attente de l'heure de départ");
            }
            else {
              $new_charging_state = 2;  // si mode immédiat
              log::add('peugeotcars','info',"Branchement de la prise de charge détecté => démarrage immédiat de la charge");
            }
          }
          else if ($charging_state == 0) {
            if (strtolower($charging_status) == "inprogress")
              $new_charging_state = 2;
            else
              $new_charging_state = $charging_state;
          }
          else if ($charging_state == 1) {
            // Attente de l'heure de charge
            if (strtolower($charging_status) == "inprogress") {
              $new_charging_state = 2;
              log::add('peugeotcars','info',"Heure de début de chargement atteint");
            }
            else
              $new_charging_state = $charging_state;
          }
          else if ($charging_state == 2) {
            // charge en cours
            if ((strtolower($charging_status) == "finished") || (strtolower($charging_status) == "stopped")) {
              $new_charging_state = 3;
              log::add('peugeotcars','info',"Fin du chargement");
            }
            else
              $new_charging_state = $charging_state;
          }
          else if ($charging_state == 3) {
            // charge en cours
            if ($charging_plugged == '0') {
              $new_charging_state = 0;
              log::add('peugeotcars','info',"Prise de charge débranchée");
            }
            else
              $new_charging_state = $charging_state;
          }
          $cmd_cs->event($new_charging_state);
          log::add('peugeotcars','debug',"charging_state:".$charging_state."/ new_charging_state:".$new_charging_state);
          
          // A minuit, mise à jour maintenance
//          if (($heure==0) && ($minute==0)) {
//            $login_ctr = $session_peugeotcars->pg_api_mym_login();
//            if ($login_ctr == "OK") {
//              $ret = $session_peugeotcars->pg_ap_mym_maintenance($vin);
//              if ($ret["success"] == "OK") {
//                log::add('peugeotcars','info',"Mise à jour date maintenance");
//                // jours jusqu'à la prochaine visite
//                $nbj_ts = round ((intval($ret["visite1_ts"]) - time())/(24*3600));
//                $cmd = $this->getCmd(null, "entretien_jours");
//                $cmd->event($nbj_ts);
//                // km jusqu'à la prochaine visite
//                $dist = intval($ret["visite1_mileage"]) - intval($ret["mileage_km"]);
//                $cmd = $this->getCmd(null, "entretien_dist");
//                $cmd->event($dist);
//              }
//              else {
//                log::add('peugeotcars','error',"Erreur d'accès à l'API pour informations de maintenance");
//              }
//            }
//            else {
//              log::add('peugeotcars','error',"Erreur login API pour informations de maintenance");
//            }
//          }
        }
      }
    }

  // =================================================================
  // Fonction d'appel au serveur MQTT (envoi de commandes à l'API PSA)
  // =================================================================
  // Command = 0x10 : preconditionning (param0 = 0:"off" - 1:"on")
  // Command = 0x20 : charging         (param0 = 0:"delayed" - 1:"immediate" / param1 = hour / param2 = minute)
  // Command = 0x30 : Wakeup           (no param)
  // Command = 0x40 : Getstate         (no param)
  public function mqtt_submit($command, ...$params) {

    // Test si deamon OK
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'nok') {
      log::add('peugeotcars', 'info', "Le démon de gestion des commandes vers le véhicule est arrêté: Commande annulée");
      return;
    }    
    log::add('peugeotcars', 'info', "mqtt_submit: Envoi de la commmande (".dechex($command).") vers le serveur local");

    // Creation d'une liaison TCP/IP avec le serveur MQTT
    $socket = mqtt_start_socket ();
    
    $nb_param = 0;
    $msg['param'] = [];
    foreach ($params as $param) {
      $msg['param'][$nb_param++] = $param;
    }

    // Envoi du message de commande
    //mqtt_message_send ($socket, $msg, $ack);
    $cr = mqtt_message_send2($socket, $command, $msg, $ack);

    // Fermeture du socket TCP/IP
    mqtt_end_socket ($socket);

    if ($cr == 0)
      log::add('peugeotcars', 'error', "mqtt_submit: Erreur lors de l'envoi de la commande vers le serveur local");
    else
      return ($ack);
  }


  // =====================================================
  // Fonction de configuration des parametres de la charge
  // =====================================================
  public function cfg_charging($use_req, $req_imm_diff=0) {
    $cmd_imm_diff = $this->getCmd(null, 'charging_imdel_val');
    $cmd_del_hour = $this->getCmd(null, 'charging_del_hour');
    if ((is_object($cmd_imm_diff)) && (is_object($cmd_del_hour))) {
      if ($use_req == 0)
        $imm_diff = $cmd_imm_diff->execCmd();
      else
        $imm_diff = $req_imm_diff;
      $del_hour = $cmd_del_hour->execCmd();
      list($del_hr, $del_mn) = explode(":", $del_hour);
      log::add('peugeotcars','info',"Envoi requete paramètres de chargement => Imm_Diff / Heure = ".$imm_diff." / ".$del_hr.":".$del_mn);
      $this->mqtt_submit(CMD_CHARGING, intval($imm_diff), intval($del_hr), intval($del_mn));
      return($imm_diff);
    }
  }

}


// Classe pour les commandes du plugin
// ===================================
class peugeotcarsCmd extends cmd 
{
    /*     * *************************Attributs****************************** */
    public function execute($_options = null) {
        //log::add('peugeotcars','info',"execute:".$_options['message']);
        if ($this->getLogicalId() == 'refresh') {
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
        elseif ( $this->getLogicalId() == 'charging_imdel_cmd') {
          $eqLogic = $this->getEqLogic();
          $cmd_ret = $eqLogic->getCmd(null, 'charging_imdel_val');
          if (is_object($cmd_ret)) {
            $value = intval($cmd_ret->execCmd());
            $cmd_ret->setCollectDate('');
            $value = ($value == 0)?1:0;
            $cmd_ret->event($value);
            // Configuration du type de chargement vers le vehicule
            $eqLogic->cfg_charging(1, $value );
          }
        }
        else if ($this->getLogicalId() == 'precond_start') {
          $eqLogic = $this->getEqLogic();
          peugeotcars::mqtt_submit(CMD_PRECOND, 1);
        }
        else if ($this->getLogicalId() == 'precond_stop') {
          $eqLogic = $this->getEqLogic();
          peugeotcars::mqtt_submit(CMD_PRECOND, 0);
        }
        else if ($this->getLogicalId() == 'charging_del_hour_cmd') {
          $new_time = $_options['message'];
          $eqLogic = $this->getEqLogic();
          $cmd_ass = $eqLogic->getCmd(null, 'charging_del_hour');
          if (is_object($cmd_ass)) {
            $cmd_ass->event($new_time);
          }
        }
        else if ($this->getLogicalId() == 'charging_batmax_cmd') {
          $new_batmax = $_options['slider'];
          $eqLogic = $this->getEqLogic();
          $cmd_ass = $eqLogic->getCmd(null, 'charging_batmax_val');
          if (is_object($cmd_ass)) {
            $cmd_ass->event($new_batmax);
          }
          // for tests
          // $cmd_cs = $eqLogic->getCmd(null, 'charging_state');
          // $new_charging_state = (intval($_options['slider']) / 5)%5;
          // $cmd_cs->event($new_charging_state);
        }
        else if ($this->getLogicalId() == 'test_mqtt1') {
          $eqLogic = $this->getEqLogic();
          peugeotcars::mqtt_submit(CMD_GET_STATE);
        }
        else if ($this->getLogicalId() == 'test_mqtt2') {
          $eqLogic = $this->getEqLogic();
          peugeotcars::mqtt_submit(CMD_WAKEUP);
          // $eqLogic->cfg_charging(1, 0);
        }

        
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
    

    /*     * **********************Getteur Setteur*************************** */
}

?>
