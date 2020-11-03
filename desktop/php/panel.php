<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
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

log::add('peugeotcars', 'debug', 'VIN:'.$vin);

?>

<script type="text/javascript" src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
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
                <img id="voiture_img" src=<?php echo "plugins/peugeotcars/ressources/$vin.png"; ?> width="400" />
              </div>
            </fieldset>
        </div>
    </div>
    <div>
      <div class="row">
      <div class="col-lg-8 col-lg-offset-2" style="padding-top:10px">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation"><a href="#trips_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Trajets}}</a></li>
          <li role="presentation" class="active"><a href="#car_info_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Informations véhicule}}</a></li>
          <li role="presentation"><a href="#car_maint_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Maintenance véhicule}}</a></li>
        </ul>
      </div>
      </div>
      <div class="row">
      <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane" id="trips_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2" style="padding-top:10px">
                  <form class="form-horizontal">
                       <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                           <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                               <i style="font-size: initial;"></i> {{Base de données des trajets}}
                           </div>
                           <div class="pull-left" style="padding-top:10px;padding-left:20px;min-height:100px;font-size: 1.5em;">
                             <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_update_database'>{{Mise à jour de la base}}</a><br>
                           </div>
                           <div class="pull-left" id='bdd_report' style="border: 1px solid #e5e5e5;padding-top:10px;padding-left:20px;min-height:100px;min-width:800px;font-size:1.0em;">
                           </div>
                       </fieldset>
                       <div style="min-height: 10px;"></div>
                   </form>
              </div>
          </div>
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2" style="height: 170px;padding-top:10px;">
                  <form class="form-horizontal">
                    <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 0px 5px;background-color:#f8f8f8">
                      <div class="form-horizontal" style="min-height: 10px;">
                      </div>
                      <div class="pull-left" style="min-height:130px;font-size: 1.5em;">
                        <i style="font-size: initial;"></i> {{Période analysée}}
                        <br>
                        Début : <input id="in_startDate" class="pull-right form-control input-sm in_datepicker" style="display : inline-block; width: 87px;" value="<?php echo $date['start']?>"/>
                        <br>
                        Fin : <input id="in_endDate" class="pull-right form-control input-sm in_datepicker" style="display : inline-block; width: 87px;" value="<?php echo $date['end']?>"/>
                      </div>
                      <div class="pull-left" style="padding-top:30px;padding-left:20px;min-height:130px;font-size: 1.5em;">
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_validChangeDate' title="{{Mise à jour des données sur la période}}">{{Mise à jour période}}</a><br>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_per_today'>{{Aujourd'hui}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_per_yesterday'>{{Hier}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_per_last_week'>{{Les 7 derniers jours}}</a>
                        <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='bt_per_all'>{{Tout}}</a>
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
                               <i style="font-size: initial;"></i> {{Statistiques d'utilisation sur cette période}}
                           </div>
                           <div id='div_hist_usage' style="font-size: 1.2em;"></div>
                           <div style="v"></div>
                       </br>
                       </fieldset>
                       <div style="min-height: 10px;"></div>
                   </form>
              </div>
          </div>
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2">
                  <form class="form-horizontal">
                       <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                           <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                               <i style="font-size: initial;"></i> {{Liste des trajets}}
                           </div>
                           <div id='div_hist_liste' style="font-size: 1.2em;"></div>
                           <div id='div_hist_liste2' style="font-size: 1.2em;">
                             <table id="trip_liste" class="display compact" width="90%"></table>
                           </div>
                           <div style="v"></div>
                       </br>
                       </fieldset>
                       <div style="min-height: 10px;"></div>
                   </form>
              </div>
          </div>

          <div class="row">
              <div class="col-lg-8 col-lg-offset-2">
                      <form class="form-horizontal">
                           <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                               <div style="padding-top:10px;padding-left:24px;padding-bottom:25px;color: #333;font-size: 1.5em;">
                                   <i style="font-size: initial;" class="fas fa-chart-line"></i> {{Distance}}
                               </div>
                               <div id='div_graphDistance'></div>

                               <div style="padding-top:10px;padding-left:24px;padding-bottom:25px;color: #333;font-size: 1.5em;">
                                   <i style="font-size: initial;" class="fas fa-chart-bar"></i> {{Temps des trajets}}
                               </div>
                               <div id='div_graphTempsTrajets'></div>

                               <div style="padding-top:10px;padding-left:24px;padding-bottom:25px;color: #333;font-size: 1.5em;">
                                   <i style="font-size: initial;" class="fas fa-chart-bar"></i> {{Vitesse moyenne}}
                               </div>
                               <div id='div_graphVitesseMoyenne'></div>
                           </br>
                           </fieldset>
                           <div style="min-height: 10px;"></div>
                       </form>
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_info_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2"  id="infos_vehicule" style="padding-top:10px">
                xxyy
              </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="car_maint_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2"  id="infos_maintenance" style="padding-top:10px">
                zz00
              </div>
          </div>
        </div>
      </div>
    </div>
    </div>

</div>
<?php include_file('desktop', 'panel', 'js', 'peugeotcars');?>