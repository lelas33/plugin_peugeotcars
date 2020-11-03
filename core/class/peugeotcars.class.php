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
        return array( "vin"                => array('Vin',              'info',  'string',    "", 0, "GENERIC_INFO",   'peugeotcars::car_img', 'peugeotcars::car_img', ''),
                      "kilometrage"        => array('Kilometrage',      'info',  'numeric',"kms", 1, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_dist"     => array('Dist.Entretien',   'info',  'numeric',"kms", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_jours"    => array('Jours.Entretien',  'info',  'numeric',  "j", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "vehicule_connecte"  => array('Vehic.Connecté',   'info',  'binary',    "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', '')
        );


    }

    // public function postSave() : Called after equipement saving
    // ==========================================================
    public function postSave()
    {
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
            if ( $id == "vin" ) {
              // Mise à jour du champ VIN a partir du logicalId
              $vin = $this->getlogicalId();
              log::add('peugeotcars','debug','postSave:add vin:'.$vin);
              $cmd->event($vin);
            }
            if ( $listValue != "" ) {
              $cmd->setConfiguration('listValue', $listValue);
            }
            $cmd->setIsHistorized($hist);
            $cmd->setDisplay('generic_type', $generic_type);
            $cmd->setTemplate('dashboard', $template_dashboard);
            $cmd->setTemplate('mobile', $template_mobile);

            $cmd->save();
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
              log::add('peugeotcars','debug','postSave:add vin:'.$vin);
              $cmd->event($vin);
            }
            if ($listValue != "") {
                $cmd->setConfiguration('listValue', $listValue);
            }
            $cmd->save();
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
      $session_peugeotcars = new peugeotcars_api_v1();
      $session_peugeotcars->login(config::byKey('token', 'peugeotcars'));
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
      // vérification de la capacité véhicule connecté
      $cmd = $this->getCmd(null, "vehicule_connecte");
      $ret = $session_peugeotcars->pg_api_me_vehicules($vin);
      if ($ret["sucess"] == "OK") {
        $cmd->event(TRUE);
      }
      else {
        $cmd->event(FALSE);
      }
      
    }

    public function preRemove() {
    }

    // Fonction appelée au rythme de 1 heure (recupeartion des informations courantes de la voiture)
    // =============================================================================================
    public static function pull() {
      // if ( config::byKey('account', 'peugeotcars') != "" || config::byKey('password', 'peugeotcars') != "" ) {
      if (config::byKey('token', 'peugeotcars') != "") {
        log::add('peugeotcars','info','pull: scan info');
        $session_peugeotcars = new peugeotcars_api_v1();
        $session_peugeotcars->login(config::byKey('token', 'peugeotcars'));
        
        // Appel API pour le kilometrage courant
        foreach (eqLogic::byType('peugeotcars') as $eqLogic) {
          $vin = $eqLogic->getlogicalId();
          $veh_con_cmd = $eqLogic->getCmd(null, "vehicule_connecte");
          $veh_con = $veh_con_cmd->execCmd();
          if (($veh_con == TRUE) && ($eqLogic->getIsEnable())) {
            $ret = $session_peugeotcars->pg_api_me_vehicules($vin);
            $cmd = $eqLogic->getCmd(null, "kilometrage");
            $kms    = $ret["mileage_km"];
            $dt_kma = date('Y-m-d H:i:s', intval($ret["mileage_ts"]));  // 2020-10-27 11:34:35
            log::add('peugeotcars', 'debug', 'VIN: '.$vin.', kms:'.$kms.', date: '.$dt_kma);
            $cmd->event($kms, $dt_kma);
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
        if ( $this->getLogicalId() == 'commande' && $_options['select'] != "" )
        {
          log::add('peugeotcars','info',"Commande execute ".$this->getLogicalId()." ".$_options['select']);
          //$session_peugeotcars = new husqvarna_api();
          //$session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'));
          $eqLogic = $this->getEqLogic();

          $order = $session_peugeotcars->control($eqLogic->getLogicalId(), $_options['select']);
          log::add('peugeotcars','debug',"Commande traité : Code = ".$order->status);
        }
        elseif ( $this->getLogicalId() == 'refresh')
        {
          log::add('peugeotcars','info',"Refresh data");
          peugeotcars::pull();
        }

        
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
?>
