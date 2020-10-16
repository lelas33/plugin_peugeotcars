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

//define("MOWER_LOG_FILE", "/../../data/mower_log.txt");
//define("MOWER_IMG_FILE", "/../../ressources/maison.png");
//const DAY_NAMES = ["dim","lun","mar","mer","jeu","ven","sam"];

// etats du mode de planification
//const MDPLN_IDLE = "Repos";

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
/*      
        return array( "batteryPercent"     => array('Batterie',               'h', 'info',  'numeric', "%", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "connected"          => array('Connecté',               'h', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "lastErrorCode"      => array('Code erreur',            'h', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "commande"           => array('Commande',               'h', 'action','select',   "", 0, "GENERIC_ACTION", '',      '',      'START|'.__('Démarrer',__FILE__).';STOP|'.__('Arrêter',__FILE__).';PARK|'.__('Ranger',__FILE__)),
                      "mowerStatus"        => array('Etat robot',             'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "operatingMode"      => array('Mode de fonctionnement', 'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "nextStartSource"    => array('Prochain départ',        'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "nextStartTimestamp" => array('Heure prochain départ',  'h', 'info',  'string',  "ut2", 0, "GENERIC_INFO", 'core::badge', 'core::badge', ''),
                      "storedTimestamp"    => array('Heure dernier rapport',  'h', 'info',  'string',  "ut1", 0, "GENERIC_INFO", 'core::badge', 'core::badge', ''),
                      "errorStatus"        => array('Statut erreur',          'p', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_en"        => array('Planification cmd',      'p', 'action','other',    "", 0, "GENERIC_ACTION", 'custom::IconActionNt', 'custom::IconActionNt',      ''),
                      "planning_activ"     => array('Planification',          'p', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "planning_state"     => array('Etat planification',     'p', 'info',  'string',   "", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "planning_nbcy_tot"  => array('Nombre de cycles total', 'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "planning_nbcy_z1"   => array('Nombre de cycles zone1', 'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "meteo_en"           => array('Météo cmd',              'p', 'action','other',    "", 0, "GENERIC_ACTION", 'custom::IconActionNt', 'custom::IconActionNt',      ''),
                      "meteo_activ"        => array('Météo',                  'p', 'info',  'binary',   "", 0, "GENERIC_INFO",   'core::alert', 'core::alert', ''),
                      "lastLocations"      => array('Position GPS',           'h', 'info',  'string',   "", 0, "GENERIC_INFO",   'husqvarna::maps_husqvarna', 'husqvarna::maps_husqvarna', ''),
                      "gps_posx"           => array('GPS position X',         'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', ''),
                      "gps_posy"           => array('GPS position Y',         'p', 'info',  'numeric',  "", 0, "GENERIC_INFO",   'core::line', 'core::line', '')
        );
*/
        return array( "vin"                => array('Vin',              'info',  'string',    "", 0, "GENERIC_INFO",   'peugeotcars::car_img', 'peugeotcars::car_img', ''),
                      "kilometrage"        => array('Kilometrage',      'info',  'numeric',"kms", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_dist"     => array('Dist.Entretien',   'info',  'numeric',"kms", 0, "GENERIC_INFO",   'core::badge', 'core::badge', ''),
                      "entretien_jours"    => array('Jours.Entretien',  'info',  'numeric',  "j", 0, "GENERIC_INFO",   'core::badge', 'core::badge', '')
        );


    }

    //public function postUpdate()
    public function postSave()
    {
        foreach( $this->getListeDefaultCommandes() as $id => $data)
        {
            list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue) = $data;
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
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);

                $cmd->save();
            }
            else
            {
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setUnite($unit);
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                if ( $id == "vin" ) {
                  // Mise à jour du champ VIN a partir du logicalId
                  $vin = $this->getlogicalId();
                  log::add('peugeotcars','debug','postSave:add vin:'.$vin);
                  $cmd->event($vin);
                }
                if ( $listValue != "" )
                {
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
      
    }

    public function preRemove() {
    }

    // Fonction appelée au rythme de 1 mn
    public static function pull() {
        if ( config::byKey('account', 'peugeotcars') != "" || config::byKey('password', 'peugeotcars') != "" )
        {
            log::add('peugeotcars','debug','scan movers info');
            foreach (self::byType('peugeotcars') as $eqLogic) {
                $eqLogic->scan();
            }
        }
    }



    public function scan() {
        //$session_peugeotcars = new husqvarna_api();
        //$session_peugeotcars->login(config::byKey('account', 'peugeotcars'), config::byKey('password', 'peugeotcars'));

        //$session_peugeotcars->logOut();
    }
}

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
