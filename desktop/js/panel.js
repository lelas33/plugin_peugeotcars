
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
var car_trips = [];
var car_gps   = [];
var trip_polyline = [];
var gps_macarte = null;
var gps_pts_nb = 0;

const TRIPS_COLOR_NAMES = [
  "Aquamarine",
  "Blue",
  "BlueViolet",
  "Brown",
  "Chocolate",
  "Coral",
  "Cyan",
  "DarkBlue",
  "DarkCyan",
  "Aqua",
  "DarkGrey",
  "DarkRed",
  "DarkSalmon",
  "DimGrey",
  "Fuchsia",
  "Gold",
  "Green",
  "GreenYellow",
  "Indigo",
  "LightGreen",
  "LightPink",
  "LightSalmon",
  "LightYellow",
  "Lime",
  "Magenta",
  "MidnightBlue",
  "Orange",
  "OrangeRed",
  "Orchid",
  "PaleGoldenRod",
  "Pink",
  "Purple",
  "Red",
  "RoyalBlue",
  "Salmon",
  "SkyBlue",
  "Tomato",
  "Turquoise",
  "Violet",
  "White",
  "Yellow"
];

const NB_TRIPS_COLORS = TRIPS_COLOR_NAMES.length;
var veh_trip_loaded = 0;
var veh_info_loaded = 0;
var veh_maint_loaded = 0;

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
$(document).ready(function() {
  loadData();
  //loadDataGps();
  // Show trips on page load
  $(".tab_content").hide(); //Hide all content
  $("#car_gps_tab").addClass("active").show(); //Activate first tab

});

// Sélection des différents onglets
// ================================
$('.nav li a').click(function(){

	var selected_tab = $(this).attr("href");
  
  if (selected_tab == "#car_gps_tab") {
    if (veh_trip_loaded == 0) {
      loadData();
      veh_trip_loaded = 1;
    }
  }
  else if (selected_tab == "#car_info_tab") {
    if (veh_info_loaded == 0) {
      veh_get_infos();
      veh_info_loaded = 1;
    }
  }
  else if (selected_tab == "#car_maint_tab") {
    if (veh_maint_loaded == 0) {
      veh_get_maint();
      veh_maint_loaded = 1;
    }
  }
//	alert("Onglet:"+selected_tab);

});




// =======================================================================
//                         Gestion des trajets du véhicule
// =======================================================================

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
            car_dt = JSON.parse(data.result);
            //alert("getLogData:data nb="+nb_dt);
            // reopie les donnees recues (trajets et points GPS)
            car_trips = [];
            for (p=0; p<car_dt.trips.length; p++) {
              car_trips[p] = car_dt.trips[p];
            }
            car_gps = [];
            for (p=0; p<car_dt.gps.length; p++) {
              car_gps[p] = car_dt.gps[p];
            }
            alert("Nb trajet:"+car_trips.length+" / nb points GPS:"+car_gps.length);
            stat_usage ();
            // trip_list ();
            // Graphs();
        }
    });
}


// Calcul des statistiques d'utilisation du vehicule sur la période
// et affichage dans le div #trips_info
// ================================================================
function stat_usage () {
  var trips_number = 0;
  var trips_total_duration = 0;
  var trips_total_distance = 0;
  var trip_ts_first = Date.now()/1000;
  var trip_ts_last  = 0;

  var trips_number = car_trips.length;
  if (trips_number <1) {
    $("#trips_info").empty();
    $("#trips_info").append("Pas de données");
    return;
  }
        list($tr_tss, $tr_tse, $tr_ds, $tr_batt) = explode(",", $buffer);

  
  // analyse des données
  for (i=0; i<trips_number; i++) {
    tmp = car_trips[i].split(',');
    trip_ts_sta = parseInt  (tmp[0],10);  // trip Timestamp start
    trip_ts_end = parseInt  (tmp[1],10);  // trip Timestamp end
    trip_dist   = parseFloat(tmp[2]);     // trip distance
    trip_batt   = parseFloat(tmp[3]);     // trip battery level usage

    // gestion date premier et dernier trajet
    if (trip_ts_sta < trip_ts_first)
      trip_ts_first = trip_ts_sta;
    if (trip_ts_sta > trip_ts_last)
      trip_ts_last = trip_ts_sta;
    trip_dur = trip_ts_end - trip_ts_sta;
    trips_total_distance += trip_dist;
    trips_total_duration += trip_dur;
  }
  // Autres calculs
  var dur_h = Math.floor(trips_total_duration / 3600);
  var dur_m = Math.round((trips_total_duration - 3600*dur_h)/60);
  var total_distance =  Math.round(trips_total_distance*10.0)/10.0;
  var vit_moyenne = Math.round((36000.0 * trips_total_distance) / trips_total_duration)/10.0;
  var ts_first = new Date(trip_ts_first * 1000);
  var ts_last  = new Date(trip_ts_last * 1000);
  
  // Affichage des résultats dans le DIV:"trips_info"
  $("#trips_info").empty();
  $("#trips_info").append("Nombre de trajets: "+trips_number+"  (du "+ts_first.toLocaleDateString()+" au "+ts_last.toLocaleDateString()+")<br>");
  $("#trips_info").append("Distance totale: "+total_distance+" kms<br>");
  $("#trips_info").append("Temps de trajet global: "+dur_h+" h "+dur_m+" mn<br>");
  $("#trips_info").append("Vitesse moyenne sur ces trajets: "+vit_moyenne+" km/h<br>");
  
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

// gestion du bouton de definition et de mise à jour de la période pour les trajets
// ================================================================================
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
  globalEqLogic = vin;
  img = "plugins/peugeotcars/ressources/"+vin+".png";
  //alert(img);
  $('#voiture_img').attr('src', img);
  // Mise à jour infos vehicule
  veh_get_infos();
  // Mise à jour infos maintenance vehicule
  veh_get_maint();

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


// =======================================================================
//                     Gestion de l'historique GPS du véhicule
// =======================================================================
// Fonction d'initialisation de la carte
// =====================================
// On initialise la latitude et la longitude de Paris (centre de la carte)
function initMap(home_lat, home_lon) {
    // met à jour le paramètre CSS associé au DIV
    $('#trips_map').css({height:'700px'});
    // Créer l'objet "gps_macarte" et l'insèrer dans l'élément HTML qui a l'ID "trips_map"
    gps_macarte = L.map('trips_map').setView([home_lat, home_lon], 11);
    // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        // Il est toujours bien de laisser le lien vers la source des données
        attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
        minZoom: 1,
        maxZoom: 20
    }).addTo(gps_macarte);
}

// capturer les donnees GPS depuis le serveur
// ==========================================
function loadDataGps(){
    var param = [];
    param[0]= (Date.parse($('#gps_startDate').value())/1000);  // Time stamp en seconde
    param[1]= (Date.parse($('#gps_endDate').value())/1000);
    globalEqLogic = $("#eqlogic_select option:selected").val();
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getGpsData',
            eqLogic_id: globalEqLogic,  // VIN du vehicule
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadDataGps:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadDataGps] Objet peugeotcars récupéré : " + globalEqLogic);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            dt_log = JSON.parse(data.result);
            nb_dt = dt_log.log.length;
            //alert("getLogGPSData:data nb="+nb_dt);
            // Capture les donnees de position
            car_dtgpslog = [];
            for (p=0; p<nb_dt; p++) {
              car_dtgpslog[p] = dt_log.log[p];
            }
            // coordonnees domicile
            tmp = dt_log.home.split(',');
            home_lat = parseFloat(tmp[0]);
            home_lon = parseFloat(tmp[1]);
            if (gps_macarte == null)
              initMap(home_lat, home_lon);
            gps_display_points();
        }
    });
}

// Ajout de marqueurs sur la carte
// ===============================
function gps_display_points() {
  var trip_ts_first = Date.now()/1000;
  var trip_ts_last  = 0;

  // Suppression des marqueurs existants s'il y en a
  if (trip_polyline.length > 0) {
    for (i=0;i<trip_polyline.length;i++) {
      gps_macarte.removeLayer(trip_polyline[i]);
    }
    trip_polyline = [];
  }
  
  gps_pts_nb = car_dtgpslog.length;
  if (gps_pts_nb == 0) {
    $("#gps_info").empty();
    return;
  }
  // alert("gps_pts_nb="+gps_pts_nb);
  // centre la carte sur le point "Home" de Jeedom
  //alert("Home GPS:"+home_lat+"/"+home_lon);
  gps_macarte.setView([home_lat, home_lon], 11);
  // analyse des données pour la création d'une liste de trajets
  // (un trajet est identifié par une interruption supérieure à 5 mn entre 2 points GPS sucessifs)
  trip_polyline = [];
  var trip_table = [];
  var trip = [];
  var last_pts = [];
  var gps_pt_ts_prev = 0;
  var nb_trip = 0;
  for (i=0; i<gps_pts_nb; i++) {
    tmp = car_dtgpslog[i].split(',');
    gps_pt_ts   = parseInt  (tmp[0],10);  // Timestamp
    gps_pt_lat  = parseFloat(tmp[1]);     // Lat
    gps_pt_lon  = parseFloat(tmp[2]);     // Lon
    gps_pt_head = parseFloat(tmp[3]);     // Heading
    batt_level  = parseFloat(tmp[4]);     // Battery level

    // Identification date/heure du premier et dernier trajet
    if (gps_pt_ts < trip_ts_first)
      trip_ts_first = gps_pt_ts;
    if (gps_pt_ts > trip_ts_last)
      trip_ts_last = gps_pt_ts;
    // séparation des trajets
    if (i==0) {
      trip = [];
      trip.push([gps_pt_lat, gps_pt_lon]);
    }
    else if (gps_pt_ts - gps_pt_ts_prev>(5*60)) {
      // fin d'un trajet
      trip_table.push(trip);
      nb_trip +=1;
      // début d'un nouveau trajet
      trip = [];
      trip.push(last_pts);
      trip.push([gps_pt_lat, gps_pt_lon]);
    }
    else {
      trip.push([gps_pt_lat, gps_pt_lon]);
    }
    gps_pt_ts_prev = gps_pt_ts;
    last_pts = [gps_pt_lat, gps_pt_lon];
  }
  // Ajout du dernier trajet s'il n'est pas monopoint
  if (trip.length > 1) {
    trip_table.push(trip);
    nb_trip +=1;
  } 
  // Affiche les infos sur les trajets de la période
  var ts_first = new Date(trip_ts_first * 1000);
  var ts_last  = new Date(trip_ts_last * 1000);
  $("#gps_info").empty();
  $("#gps_info").append("Nombre de points GPS: "+gps_pts_nb+"<br>");
  $("#gps_info").append("Nombre de trajets: "+nb_trip+"  (du "+ts_first.toLocaleString()+" au "+ts_last.toLocaleString()+")<br>");

  // affichage des trajets
  for (trip_id=0; trip_id<nb_trip; trip_id++) {
    trip_polyline[trip_id] = L.polyline(trip_table[trip_id], {color: TRIPS_COLOR_NAMES[trip_id%NB_TRIPS_COLORS], weight: 5, smoothFactor: 1}).addTo(gps_macarte);
  }
}


// gestion des boutons de definition et de mise à jour de la période pour les logs GPS
// ===================================================================================
$('#btgps_validChangeDate').on('click',function(){
  loadData();
});

// Aujourd'hui
$('#btgps_per_today').on('click',function(){
  $('#gps_startDate').datepicker( "setDate", "+0" );
  $('#gps_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Hier
$('#btgps_per_yesterday').on('click',function(){
  $('#gps_startDate').datepicker( "setDate", "-1" );
  $('#gps_endDate').datepicker( "setDate", "+0" );
  loadData();
});
// Les 7 derniers jours
$('#btgps_per_last_week').on('click',function(){
  $('#gps_startDate').datepicker( "setDate", "-6" );
  $('#gps_endDate').datepicker( "setDate", "+1" );
  loadData();
});
// Tout
$('#btgps_per_all').on('click',function(){
  $('#gps_startDate').datepicker( "setDate", "-730" );  // - 2 ans
  $('#gps_endDate').datepicker( "setDate", "+1" );
  loadData();
});


// =======================================================================
//                         Gestion des informations du véhicule
// =======================================================================
// Interroger le serveur pour obtenir les informations du véhicule
// ===============================================================
function veh_get_infos(){
    var param = [];
    globalEqLogic = $("#eqlogic_select option:selected").val();
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getVehInfos',
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
            info_cars = JSON.parse(data.result);
            //alert("retour:="+data.result);

            veh_disp_infos(info_cars);
        }
    });
}


// Affichage des informations sur le vehicule
// ==========================================
function veh_disp_infos(info_cars){
  
  $("#infos_vehicule").empty();

  // Section charactéristiques
  $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Charactéristiques du véhicule</p>");
  $("#infos_vehicule").append("<b>Numero VIN:</b>"+info_cars.vin+"<br>");
  $("#infos_vehicule").append("<b>Numero LCDV:</b>"+info_cars.lcdv+"<br>");
  $("#infos_vehicule").append("<b>Nom du véhicule:</b> "+info_cars.short_label+"<br>");
  $("#infos_vehicule").append("<b>Date de début de garantie:</b> "+info_cars.warranty_start_date+"<br>");
  $("#infos_vehicule").append("<b>Fonctions intégrées:</b> "+info_cars.eligibility+"<br>");
  $("#infos_vehicule").append("<b>Type:</b> "+info_cars.types+"<br><br>");
 
  // Section valeurs courantes
  $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Valeurs courantes du véhicule</p>");
  $("#infos_vehicule").append("<b>Kilométrage courant:</b> "+info_cars.mileage_km+" kms, ");
  $("#infos_vehicule").append("<b>A la date du :</b> "+info_cars.mileage_ts+"<br><br>");

  // Section version logicielle
  $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Version logicielles disponibles</p>");
  $("#infos_vehicule").append("<b>LOGICIEL:</b> "+info_cars.rcc_type+"<br>");
  $("#infos_vehicule").append("<b>Version courante connue:</b> "+info_cars.rcc_current_ver+"<br>");
  $("#infos_vehicule").append("<b>Version disponible:</b> "+info_cars.rcc_available_ver+" (datée du "+info_cars.rcc_available_date+")<br>");
  $("#infos_vehicule").append("<b>Taille du fichier:</b> "+Math.round(parseInt(info_cars.rcc_available_size,10)/(1024*1024))+" MB.<br>");
  $("#infos_vehicule").append("<b>Liens de téléchargement:</b><br>");
  $("#infos_vehicule").append("<b> . Fichier:</b><br>"+info_cars.rcc_available_UpURL+"<br>");
  $("#infos_vehicule").append("<b> . Licence:</b><br>"+info_cars.rcc_available_LiURL+"<br>");
}

// =============================================================================================
//                       Gestion des informations de maintenance du véhicule
// =============================================================================================
// Interroger le serveur pour obtenir les informations de maintenance du véhicule
// ==============================================================================
function veh_get_maint(){
    var param = [];
    globalEqLogic = $("#eqlogic_select option:selected").val();
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getVehMaint',
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
            maint_cars = JSON.parse(data.result);
            //alert("retour:="+data.result);

            veh_disp_maint(maint_cars);
        }
    });
}


// Affichage des informations sur le vehicule
// ==========================================
function veh_disp_maint(maint_cars){
  
  $("#infos_maintenance").empty();

  // Section générale maintenance
  $("#infos_maintenance").append("<p style='font-size: 1.5em;color:Cyan;'>Maintenance du véhicule</p>");
  $("#infos_maintenance").append("<b>Kilométrage courant: </b>"+maint_cars.mileage_km+" kms.<br>");

  // Section première visite
  $("#infos_maintenance").append("<p style='font-size:1.5em;color:red;'><br>Prochaine visite d'entretien du véhicule. Date recommandée: "+maint_cars.visite1_date+"</p>");
  $("#infos_maintenance").append("<b>Conditions de cette visite: </b>"+maint_cars.visite1_conditions+"<br>");
  $("#infos_maintenance").append("<b>Objectifs de cette visite: </b><br>");
  for (i=0; i<maint_cars.visite1_lb_title.length; i++) {
    $("#infos_maintenance").append("-"+maint_cars.visite1_lb_title[i]+"<br>");
    for (j=0; j<maint_cars.visite1_lb_body[i].length; j++) {
      $("#infos_maintenance").append("&nbsp&nbsp&nbsp&nbsp."+maint_cars.visite1_lb_body[i][j]+"<br>");
    }
  }

  // Section seconde visite
  $("#infos_maintenance").append("<p style='font-size: 1.5em;color:red;'><br>Visite d'entretien suivante du véhicule. Date recommandée: "+maint_cars.visite2_date+"</p>");
  $("#infos_maintenance").append("<b>Conditions de cette visite: </b>"+maint_cars.visite2_conditions+"<br>");
  $("#infos_maintenance").append("<b>Objectifs de cette visite: </b><br>");
  for (i=0; i<maint_cars.visite2_lb_title.length; i++) {
    $("#infos_maintenance").append("-"+maint_cars.visite2_lb_title[i]+"<br>");
    for (j=0; j<maint_cars.visite2_lb_body[i].length; j++) {
      $("#infos_maintenance").append("&nbsp&nbsp&nbsp&nbsp."+maint_cars.visite2_lb_body[i][j]+"<br>");
    }
  }

  // Section troisieme visite
  $("#infos_maintenance").append("<p style='font-size: 1.5em;color:red;'><br>Visite d'entretien suivante du véhicule. Date recommandée: "+maint_cars.visite3_date+"</p>");
  $("#infos_maintenance").append("<b>Conditions de cette visite: </b>"+maint_cars.visite3_conditions+"<br>");
  $("#infos_maintenance").append("<b>Objectifs de cette visite: </b><br>");
  for (i=0; i<maint_cars.visite3_lb_title.length; i++) {
    $("#infos_maintenance").append("-"+maint_cars.visite3_lb_title[i]+"<br>");
    for (j=0; j<maint_cars.visite3_lb_body[i].length; j++) {
      $("#infos_maintenance").append("&nbsp&nbsp&nbsp&nbsp."+maint_cars.visite3_lb_body[i][j]+"<br>");
    }
  }


}
