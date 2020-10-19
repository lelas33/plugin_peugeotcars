
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
 var globalEqLogic = $("#eqlogic_select option:selected").val();
 var isCoutVisible = false;
$(".in_datepicker").datepicker();

// Variables partagées
var car_dtlog = [];

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
loadData();

// capturer les donnees depuis le serveur
// ======================================
function loadData(){
    var param = [];
    param[0]= (Date.parse($('#in_startDate').value())/1000);  // Time stamp en seconde
    param[1]= (Date.parse($('#in_endDate').value())/1000);
    globalEqLogic = $("#eqlogic_select option:selected").val();
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getTripData',
            eqLogic_id: globalEqLogic,  // VIN du vehicule
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadData] Objet peugeotcars récupéré : " + globalEqLogic);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            dt_log = JSON.parse(data.result);
            nb_dt = dt_log.log.length;
            //alert("getLogData:data nb="+nb_dt);
            // Capture les donnees de position
            car_dtlog = [];
            for (p=0; p<nb_dt; p++) {
              car_dtlog[p] = dt_log.log[p];
            }
            stat_usage ();
            trip_list ();
            Graphs();
        }
    });
}


// Calcul des statistiques d'utilisation du vehicule sur la période
// et affichage dans le div #div_hist_usage
// ================================================================
function stat_usage () {
  var trips_number = 0;
  var trips_total_duration = 0;
  var trips_total_distance = 0;
  var trip_ts_first = Date.now()/1000;
  var trip_ts_last  = 0;

  var trips_number = car_dtlog.length;
  if (trips_number <1) {
    $("#div_hist_usage").empty();
    $("#div_hist_usage").append("Pas de données");
    return;
  }
  // analyse des données
  for (i=0; i<trips_number; i++) {
    tmp = car_dtlog[i].split(',');
    trip_id     = parseInt  (tmp[0],10);  // trip ID
    trip_ts_sta = parseInt  (tmp[1],10);  // trip Timestamp start
    trip_ts_end = parseInt  (tmp[2],10);  // trip Timestamp end
    trip_ds_sta = parseFloat(tmp[3]);     // trip Mileage start
    trip_ds_end = parseFloat(tmp[4]);     // trip Mileage end
    trip_ds_val = parseFloat(tmp[5]);     // trip distance (Not available when .myp file from Iphone)
    trip_mt_day = parseInt  (tmp[6],10);  // Car days to maintenance
    trip_mt_dis = parseFloat(tmp[7]);     // Car distance to maintenance
    // gestion date premier et dernier trajet
    if (trip_ts_sta < trip_ts_first)
      trip_ts_first = trip_ts_sta;
    if (trip_ts_sta > trip_ts_last)
      trip_ts_last = trip_ts_sta;
    trip_dur = trip_ts_end - trip_ts_sta;
    trips_total_distance += trip_ds_end-trip_ds_sta;
    trips_total_duration += trip_dur;
  }
  // Autres calculs
  var dur_h = Math.floor(trips_total_duration / 3600);
  var dur_m = Math.round((trips_total_duration - 3600*dur_h)/60);
  var total_distance =  Math.round(trips_total_distance*10.0)/10.0;
  var vit_moyenne = Math.round((36000.0 * trips_total_distance) / trips_total_duration)/10.0;
  var ts_first = new Date(trip_ts_first * 1000);
  var ts_last  = new Date(trip_ts_last * 1000);
  
  // Affichage des résultats dans le DIV:"div_hist_usage"
  $("#div_hist_usage").empty();
  $("#div_hist_usage").append("Nombre de trajets: "+trips_number+"  (du "+ts_first.toLocaleDateString()+" au "+ts_last.toLocaleDateString()+")<br>");
  $("#div_hist_usage").append("Distance totale: "+total_distance+" kms<br>");
  $("#div_hist_usage").append("Temps de trajet global: "+dur_h+" h "+dur_m+" mn<br>");
  $("#div_hist_usage").append("Vitesse moyenne sur ces trajets: "+vit_moyenne+" km/h<br>");
  
}

// Liste des trajets du vehicule sur la période
// et affichage dans le div #div_hist_liste
// ============================================
function trip_list () {
  var trips_number = 0;

  var trips_number = car_dtlog.length;
  if (trips_number <1) {
    if ( $.fn.dataTable.isDataTable( '#trip_liste' ) ) {
      $('#trip_liste').DataTable().destroy();
    }
    var x = document.getElementById("div_hist_liste2");
    x.style.display = "none";
    $("#div_hist_liste").empty();
    $("#div_hist_liste").append("Pas de données");
    return;
  }

  // Affichage des résultats dans le DIV:"div_hist_liste"
  $("#div_hist_liste").empty();

  // analyse des données pour la création de la table
  var dataSet = [];
  for (i=0; i<trips_number; i++) {
    tmp = car_dtlog[i].split(',');
    trip_id     = parseInt  (tmp[0],10);  // trip ID
    trip_ts_sta = parseInt  (tmp[1],10);  // trip Timestamp start
    trip_ts_end = parseInt  (tmp[2],10);  // trip Timestamp end
    trip_ds_sta = parseFloat(tmp[3]);     // trip Mileage start
    trip_ds_end = parseFloat(tmp[4]);     // trip Mileage end
    trip_ds_val = parseFloat(tmp[5]);     // trip distance (Not available when .myp file from Iphone)
    trip_mt_day = parseInt  (tmp[6],10);  // Car days to maintenance
    trip_mt_dis = parseFloat(tmp[7]);     // Car distance to maintenance
  
    var date_ob = new Date(trip_ts_sta*1000);                 // initialize new Date object
    var year = date_ob.getFullYear();                         // year as 4 digits (YYYY)
    var month = ("0" + (date_ob.getMonth() + 1)).slice(-2);   // month as 2 digits (MM)
    var day   = ("0" + date_ob.getDate()).slice(-2);          // daye as 2 digits (DD)
    var hours = ("0" + date_ob.getHours()).slice(-2);         // hours as 2 digits (hh)
    var minutes = ("0" + date_ob.getMinutes()).slice(-2);     // minutes as 2 digits (mm)

    // date & time as YYYY-MM-DD hh:mm:ss format: 
    date_heure_str = day + "-" + month + "-" + year + " / " + hours + " h " + minutes;
    duree = trip_ts_end - trip_ts_sta;
    duree_mn = Math.round(duree/60);
    duree_str = Math.floor(duree_mn/60) + ' h ' + ("0" + duree_mn % 60).slice(-2);
    distance = trip_ds_end-trip_ds_sta;
    distance = Math.round(distance*10.0)/10.0;
    vitesse = 3600.0 * distance / duree;
    vitesse = Math.round(vitesse*10.0)/10.0;
    line = [trip_id, date_heure_str, duree_str, distance, vitesse];
    dataSet.push(line);

  }
  // generation de la table / liste des trajet : Utilisation de la librairie DataTable
  if ( $.fn.dataTable.isDataTable( '#trip_liste' ) ) {
    $('#trip_liste').DataTable().destroy();
  }
  //$('#div_hist_liste2').style.display = "block";
  var x = document.getElementById("div_hist_liste2");
  x.style.display = "block";
  $('#trip_liste').DataTable( {
      "scrollY": "500px",
      "scrollCollapse": true,
      "paging": false,
      data: dataSet,
      columns: [
          { title: "Ident." },
          { title: "Date / Heure" },
          { title: "Durée" },
          { title: "Distance (km)" },
          { title: "Vitesse moyenne (km/h)" }
      ]
  } );    
}

// gestion du bouton de definition et de mise à jour de la période 
// ===============================================================
$('#bt_validChangeDate').on('click',function(){
  loadData();
});

// Aujourd'hui
$('#bt_per_today').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "+0" );
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Hier
$('#bt_per_yesterday').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-1" );
  $('#in_endDate').datepicker( "setDate", "+0" );
  loadData();
});
// Les 7 derniers jours
$('#bt_per_last_week').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-6" );
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Tout
$('#bt_per_all').on('click',function(){
  $('#in_startDate').datepicker( "setDate", "-730" );  // - 2 ans
  $('#in_endDate').datepicker( "setDate", "+1" );
  loadData();
});

// Gestion photo de la voiture
// ===========================
function ChangeCarImage() {
  var vin = $("#eqlogic_select option:selected").val();
  img = "plugins/peugeotcars/ressources/"+vin+".png";
  //alert(img);
  $('#voiture_img').attr('src', img);
}

// Mise a jour de la base de donnees des trajets
// =============================================
$('#bt_update_database').on('click',function(){
  //alert("Update database");
  UpdateDataBase();
});

// Lancement de la fonction sur le serveur
function UpdateDataBase(){
    var param = [];
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'UpdateDataBase',
            eqLogic_id: globalEqLogic,
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadData] Objet peugeotcars récupéré : " + globalEqLogic);
            dt_log = JSON.parse(data.result);
            //alert("UpdateDataBase:"+dt_log.log);
            $('#bdd_report').empty();
            for (var i = 0; i < dt_log.log.length; i++) {
              $('#bdd_report').append(dt_log.log[i]+"<br>");
            }
        }
    });
}


// Génération des graphiques de Distance / Durées / Vitesse
// ========================================================
function Graphs() {
  var trips_number = car_dtlog.length;
  if (trips_number <1) {
     $("#div_graphDistance").empty();
     $("#div_graphTempsTrajets").empty();
     $("#div_graphVitesseMoyenne").empty();
     $("#div_graphDistance").append("Pas de données");
     $("#div_graphTempsTrajets").append("Pas de données");
     $("#div_graphVitesseMoyenne").append("Pas de données");
   return;
  }

  // préparation des données
  var dataSet_dist = [];
  var dataSet_dure = [];
  var dataSet_date = [];
  var dataSet_vite = [];
  var distance = 0;
  var duree_mn = 0;
  var date_str = "";
  var date_str_p = "";
  for (i=0; i<trips_number; i++) {
    tmp = car_dtlog[i].split(',');
    trip_id     = parseInt  (tmp[0],10);  // trip ID
    trip_ts_sta = parseInt  (tmp[1],10);  // trip Timestamp start
    trip_ts_end = parseInt  (tmp[2],10);  // trip Timestamp end
    trip_ds_sta = parseFloat(tmp[3]);     // trip Mileage start
    trip_ds_end = parseFloat(tmp[4]);     // trip Mileage end
    trip_ds_val = parseFloat(tmp[5]);     // trip distance (Not available when .myp file from Iphone)
    trip_mt_day = parseInt  (tmp[6],10);  // Car days to maintenance
    trip_mt_dis = parseFloat(tmp[7]);     // Car distance to maintenance
  
    var date_ob = new Date(trip_ts_sta*1000);                 // initialize new Date object
    var year = date_ob.getFullYear();                         // year as 4 digits (YYYY)
    var month = ("0" + (date_ob.getMonth() + 1)).slice(-2);   // month as 2 digits (MM)
    var day   = ("0" + date_ob.getDate()).slice(-2);          // daye as 2 digits (DD)
    var hours = ("0" + date_ob.getHours()).slice(-2);         // hours as 2 digits (hh)
    var minutes = ("0" + date_ob.getMinutes()).slice(-2);     // minutes as 2 digits (mm)

    // date & time as YYYY-MM-DD hh:mm:ss format: 
    date_str = day + "-" + month + "-" + year;
    duree_str = Math.floor(duree_mn/60) + ' h ' + ("0" + duree_mn % 60).slice(-2);
    if (i == 0) {
      distance = trip_ds_end-trip_ds_sta;
      duree = trip_ts_end - trip_ts_sta;
      date_str_p = date_str;
    }
    else if (date_str_p == date_str) {
        distance += trip_ds_end-trip_ds_sta;
        duree += trip_ts_end - trip_ts_sta;
        date_str_p = date_str;
    }
    else {
      // dernier trajet du jour au cycle précédent => on valide l'entrée
      vitesse = 3600.0 * distance / duree;
      vitesse = Math.round(vitesse*10.0)/10.0;
      distance = Math.round(distance*10.0)/10.0;
      duree_mn = Math.round(duree/60);
      dataSet_date.push(date_str_p);
      dataSet_dure.push(duree_mn);
      dataSet_dist.push(distance);
      dataSet_vite.push(vitesse);
      distance = trip_ds_end-trip_ds_sta;
      duree = trip_ts_end - trip_ts_sta;
      date_str_p = date_str;
    }
  }

  // Ajoute le dernier trajet
  vitesse = 3600.0 * distance / duree;
  vitesse = Math.round(vitesse*10.0)/10.0;
  distance = Math.round(distance*10.0)/10.0;
  duree_mn = Math.round(duree/60);
  dataSet_date.push(date_str_p);
  dataSet_dure.push(duree_mn);
  dataSet_dist.push(distance);
  dataSet_vite.push(vitesse);

  // Graphique des distance
  Highcharts.chart('div_graphDistance', {
      chart: {
          type: 'column'
      },
      title: {
          text: 'Distances parcourues'
      },
      xAxis: {
          categories: dataSet_date,
          crosshair: true
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Distance (km)'
          }
      },
      tooltip: {
          headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
          pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
              '<td style="padding:0"><b>{point.y:.1f} km</b></td></tr>',
          footerFormat: '</table>',
          shared: true,
          useHTML: true
      },
      plotOptions: {
          column: {
              pointPadding: 0.2,
              borderWidth: 0
          }
      },
      series: [{
          name: 'Distance',
          color: '#4040FF',
          data: dataSet_dist

      }]
  });

  // Graphique des durées
  Highcharts.chart('div_graphTempsTrajets', {
      chart: {
          type: 'column'
      },
      title: {
          text: 'Durées des trajets'
      },
      xAxis: {
          categories: dataSet_date,
          crosshair: true
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Durée (mn)'
          }
      },
      tooltip: {
          headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
          pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
              '<td style="padding:0"><b>{point.y:.1f} mn</b></td></tr>',
          footerFormat: '</table>',
          shared: true,
          useHTML: true
      },
      plotOptions: {
          column: {
              pointPadding: 0.2,
              borderWidth: 0
          }
      },
      series: [{
          name: 'Durée',
          color: '#20FF0D',
          data: dataSet_dure

      }]
  });

  // Graphique des vitesses
  Highcharts.chart('div_graphVitesseMoyenne', {
      chart: {
          type: 'column'
      },
      title: {
          text: 'Vitesse moyenne des trajets'
      },
      xAxis: {
          categories: dataSet_date,
          crosshair: true
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Vitesse (km/h)'
          }
      },
      tooltip: {
          headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
          pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
              '<td style="padding:0"><b>{point.y:.1f} km/h</b></td></tr>',
          footerFormat: '</table>',
          shared: true,
          useHTML: true
      },
      plotOptions: {
          column: {
              pointPadding: 0.2,
              borderWidth: 0
          }
      },
      series: [{
          name: 'Vitesse',
          color: '#FF530D',
          data: dataSet_vite

      }]
  });


}