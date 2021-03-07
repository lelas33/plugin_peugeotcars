<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

include_file('3rdparty', 'DataTables/DataTables-1.10.22/js/jquery.dataTables.min', 'js', 'peugeotcars');
include_file('3rdparty', 'DataTables/DataTables-1.10.22/css/jquery.dataTables.min', 'css', 'peugeotcars');
include_file('3rdparty', 'leaflet_v1.7.1/leaflet', 'js', 'peugeotcars');
include_file('3rdparty', 'leaflet_v1.7.1/leaflet', 'css', 'peugeotcars');
$date = array(
    'start' => date('Y-m-d', strtotime(config::byKey('history::defautShowPeriod') . ' ' . date('Y-m-d'))),
    'end' => date('Y-m-d'),
);
sendVarToJS('eqType', 'peugeotcars');
sendVarToJs('object_id', init('object_id'));
$eqLogics = eqLogic::byType('peugeotcars');
$eqLogic = $eqLogics[0];
if (isset($_GET["car"]))
  $vin = $_GET["car"];
else
  $vin = $eqLogic->getlogicalId();

log::add('peugeotcars', 'debug', 'Pannel: VIN:'.$vin);

?>

<div class="row" id="div_peugeotcars">
    <div class="row">
        <div class="col-lg-8 col-lg-offset-2" style="height: 250px;padding-top:10px">
            <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 0px 5px;background-color:#f8f8f8">
              <div class="pull-left" style="padding-top:10px;padding-left:24px;color: #333;font-size: 1.5em;"> <span id="spanTitreResume">Sélection parmis vos véhicules</span>
                <select id="eqlogic_select" onchange="ChangeCarImage()" style="color:#555;font-size: 15px;border-radius: 3px;border:1px solid #ccc;">
                <?php
                foreach ($eqLogics as $eqLogic) {
                  if ($vin == $eqLogic->getlogicalId())
                    echo '<option selected value="' . $eqLogic->getlogicalId() . '">"' . $eqLogic->getHumanName(true) . '"</option>';
                  else
                    echo '<option value="' . $eqLogic->getlogicalId() . '">"' . $eqLogic->getHumanName(true) . '"</option>';
                }
                ?>
                </select>
              </div>
              <div class="pull-right" style="min-height: 30px;">
                <img id="voiture_img" src=<?php echo "plugins/peugeotcars/data/$vin/img0.png"; ?> width="400" />
              </div>
            </fieldset>
        </div>
    </div>
    <div>
      <div class="row">
      <div class="col-lg-8 col-lg-offset-2" style="padding-top:10px">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#car_trips_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Trajets}}</a></li>
          <li role="presentation"><a href="#car_stat_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Statistiques}}</a></li>
          <li role="presentation"><a href="#car_config_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Configuration véhicule}}</a></li>
          <li role="presentation"><a href="#car_info_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Informations véhicule}}</a></li>
          <li role="presentation"><a href="#car_maint_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Maintenance véhicule}}</a></li>
        </ul>
      </div>
      </div>
      <div class="row">
      <div class="tab-content" style="height:1200px;">
        <div role="tabpanel" class="tab-pane" id="car_trips_tab">
          <div class="row">
            <div class="col-lg-8 col-lg-offset-2" style="height: 150px;padding-top:10px;">
              <form class="form-horizontal">
                <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 0px 5px;background-color:#f8f8f8">
                  <div style="min-height: 10px;">
                  </div>
                  <div style="min-height:40px;font-size: 1.5em;">
                    <i style="font-size: initial;"></i> {{Période analysée}}
                  </div>
                  <div style="min-height:30px;">
                    <div class="pull-left" style="font-size: 1.3em;"> Début:
                      <input id="gps_startDate" class="pull-right form-control input-sm in_datepicker" style="display : inline-block; width: 87px;" value="<?php echo $date['start']?>"/>
                    </div>
                    <div class="pull-left" style="font-size: 1.3em;">Fin:
                      <input id="gps_endDate" class="pull-right form-control input-sm in_datepicker" style="display : inline-block; width: 87px;" value="<?php echo $date['end']?>"/>
                    </div>
                    <a style="margin-left:5px" class="pull-left btn btn-primary btn-sm tooltips" id='btgps_validChangeDate' title="{{Mise à jour des données sur la période}}">{{Mise à jour période}}</a><br>
                  </div>
                  <div style="min-height:50px;">
                    <div style="padding-top:10px;font-size: 1.5em;">
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btgps_per_today'>{{Aujourd'hui}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btgps_per_yesterday'>{{Hier}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btgps_per_this_week'>{{Cette semaine}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btgps_per_last_week'>{{Les 7 derniers jours}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btgps_per_all'>{{Tout}}</a>
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
            <div class="col-lg-2">
            </div>
          </div>
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2">
                  <form class="form-horizontal">
                       <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                           <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                               <i style="font-size: initial;"></i> {{Historique des trajets réalisés sur cette période}}
                           </div>
                           <div id='trips_info' style="font-size: 1.2em;"></div>
                           <div style="v"></div>
                       </br>
                       </fieldset>
                       <div style="min-height: 10px;"></div>
                   </form>
              </div>
              <div class="col-lg-8 col-lg-offset-2">
                <div id="trips_list" style="float:left;width:45%">
                  <div id='div_hist_liste' style="font-size: 1.2em;"></div>
                  <div id='div_graph_alti'  style="padding-top:10px;min-height:200px;"></div>
                  <div id='div_hist_liste2' style="font-size: 1.2em;">
                    <table id="trip_liste" class="display compact" width="100%"></table>
                  </div>
                </div>
                <div id="trips_separ" style="margin-left:45%;width:1%">
                </div>
                <div id="trips_map" style="margin-left:46%;width:54%">
                </div>
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_stat_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2" style="padding-top:10px">
                <form class="form-horizontal">
                     <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                         <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                             <i style="font-size: initial;"></i> {{Statistiques par mois sur les trajets réalisés}}
                         </div>
                         <div style="min-height: 30px;">
                           <img src="plugins/peugeotcars/desktop/php/distance.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Distances parcourues}}</i>
                         </div>
                         <div id='div_graph_stat_dist' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/peugeotcars/desktop/php/consommation.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Consommation du véhicule au 100 km}}</i>
                         </div>
                         <div id='div_graph_stat_conso' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/peugeotcars/desktop/php/energie.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Energie consommée et coût estimé}}</i>
                         </div>
                         <div id='div_graph_stat_energy' style="font-size: 1.2em;"></div>
                     </fieldset>
                     <div style="min-height: 10px;"></div>
                 </form>
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_config_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2">
                  <form class="form-horizontal">
                      <div style="min-height: 10px;">
                      </div>
                       <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                           <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                               <i style="font-size: initial;"></i> {{Configuration de la charge}}
                               <p style='font-size: 0.8em;color:grey;'><br>
                                 <input type="radio" id="charge_mode1" name="charge_mode" value="mode1" checked>
                                 <b>Mode 1:</b> Démarrage de la charge lorsque la prise est branchée, et arrêt lorsque la valeur maxi est atteinte<br><br>
                                 <input type="radio" id="charge_mode2" name="charge_mode" value="mode2">
                                 <b>Mode 2:</b> Démarrage de la charge en différé à l'heure programmée, et arrêt lorsque la valeur maxi est atteinte<br>
                                 (Départ et fin de charge piloté par le véhicule)<br><br>
                                 <input type="radio" id="charge_mode3" name="charge_mode" value="mode3">
                                 <b>Mode 3:</b> Démarrage de la charge en différé à l'heure programmée, et arrêt lorsque la valeur maxi est atteinte<br>
                                 (Départ et fin de charge piloté par un commutateur controlé par Jeedom)<br><br>
                               </p>
                           </div>
                           <div id='trips_info' style="font-size: 1.0em;"></div>
                           <div style="v"></div>
                       </br>
                       </fieldset>
                       <div style="min-height: 10px;"></div>
                   </form>
              </div>
              <div class="col-lg-8 col-lg-offset-2">
                  <form class="form-horizontal">
                     <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                        <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                          <i style="font-size: initial;"></i> {{Configuration du Préconditionnement}}
                        </div>
                        <div id="precond_program">
                        </div>
                     </br>
                      <div style="padding-top:10px;min-height:45px;font-size: 1.5em;">
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpp_load'>{{Charger local}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpp_save'>{{Sauvegarder local}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpp_getfromcar'>{{Importer depuis véhicule}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpp_pushtocar'>{{Envoyer vers véhicule}}</a>
                      </div>
                     </fieldset>
                  </form>
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_info_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2"  id="infos_vehicule" style="padding-top:10px">
                Chargement en cours
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_maint_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2"  id="infos_maintenance" style="padding-top:10px">
                Développement en cours
              </div>
          </div>
        </div>
      </div>
    </div>
    </div>
</div>
<?php include_file('desktop', 'panel', 'js', 'peugeotcars');?>
