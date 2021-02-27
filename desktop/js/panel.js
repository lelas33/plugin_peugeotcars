
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
var sel_trip_dist = [];
var sel_trip_alti = [];
var trip_polyline = [];
var gps_macarte = null;
var gps_pts_nb = 0;
var table_trips = null;
var veh_batt_cap = 0;   // Capacité batterie vehicule

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
var veh_stat_loaded = 0;
var veh_info_loaded = 0;
var veh_maint_loaded = 0;

const DAY_NAME = ["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"];

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
$(document).ready(function() {
  loadData();
  // Show trips after page load
  $(".tab_content").hide(); //Hide all content
  $("#car_trips_tab").addClass("active").show(); //Activate first tab

});

// Sélection des différents onglets
// ================================
$('.nav li a').click(function(){

	var selected_tab = $(this).attr("href");
  
  if (selected_tab == "#car_trips_tab") {
    if (veh_trip_loaded == 0) {
      loadData();
      veh_trip_loaded = 1;
    }
  }
  else if (selected_tab == "#car_stat_tab") {
    if (veh_stat_loaded == 0) {
      loadStats();
      veh_stat_loaded = 1;
    }
  }
  else if (selected_tab == "#car_info_tab") {
    if (veh_info_loaded == 0) {
      veh_get_infos();
      veh_info_loaded = 1;
    }
  }
  // else if (selected_tab == "#car_maint_tab") {
    // if (veh_maint_loaded == 0) {
      // veh_get_maint();
      // veh_maint_loaded = 1;
    // }
  // }
});


// =======================================================================
//                         Gestion des trajets du véhicule
// =======================================================================

// capturer les donnees depuis le serveur
// ======================================
function loadData(){
    var param = [];
    param[0]= (Date.parse($('#gps_startDate').value())/1000);  // Time stamp en seconde
    param[1]= (Date.parse($('#gps_endDate').value())/1000);
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
            veh_batt_cap = car_dt.cfg_batt_capacity;
            // reopie les donnees recues (trajets et points GPS)
            car_trips = [];
            for (p=0; p<car_dt.trips.length; p++) {
              car_trips[p] = car_dt.trips[p];
            }
            car_gps = [];
            for (p=0; p<car_dt.gps.length; p++) {
              car_gps[p] = car_dt.gps[p];
            }
            //alert("Nb trajet:"+car_trips.length+" / nb points GPS:"+car_gps.length);
            stat_usage ();
            trip_list ();
            // coordonnees domicile
            tmp = car_dt.home.split(',');
            home_lat = parseFloat(tmp[0]);
            home_lon = parseFloat(tmp[1]);
            if (gps_macarte == null)
              initMap(home_lat, home_lon);
            display_trips(-1);
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
  var trips_total_conso = 0;
  var trip_ts_first = Date.now()/1000;
  var trip_ts_last  = 0;

  trips_number = car_trips.length;
  if (trips_number <1) {
    $("#trips_info").empty();
    $("#trips_info").append("Pas de données");
    return;
  }
 
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
    trips_total_conso += trip_batt;

  }
  // Autres calculs
  var dur_h = Math.floor(trips_total_duration / 3600);
  var dur_m = Math.round((trips_total_duration - 3600*dur_h)/60);
  var total_distance =  Math.round(trips_total_distance*10.0)/10.0;
  var vit_moyenne = Math.round((36000.0 * trips_total_distance) / trips_total_duration)/10.0;
  var ts_first = new Date(trip_ts_first * 1000);
  var ts_last  = new Date(trip_ts_last * 1000);
  var total_conso = (trips_total_conso*veh_batt_cap)/100.0;
  var conso_moyenne = (total_conso*100)/total_distance;
  
  // Affichage des résultats dans le DIV:"trips_info"
  $("#trips_info").empty();
  $("#trips_info").append("Nombre de trajets: "+trips_number+"  (du "+DAY_NAME[ts_first.getDay()]+" "+ts_first.toLocaleDateString()+" au "+DAY_NAME[ts_last.getDay()]+" "+ts_last.toLocaleDateString()+")<br>");
  $("#trips_info").append("Distance totale: "+total_distance+" kms<br>");
  $("#trips_info").append("Temps de trajet global: "+dur_h+" h "+dur_m+" mn<br>");
  $("#trips_info").append("Vitesse moyenne sur ces trajets: "+vit_moyenne+" km/h<br>");
  $("#trips_info").append("Energie totale consommée: "+total_conso+" kWh<br>");
  $("#trips_info").append("Consommation moyenne sur ces trajets: "+conso_moyenne.toFixed(1)+" kWh/100 kms<br>");
  
}

// Liste des trajets du vehicule sur la période
// et affichage dans le div #div_hist_liste2
// ============================================
function trip_list () {
  var trips_number = 0;

  // Effacement de la table en cours si existante
  if ($.fn.DataTable.isDataTable('#trip_liste')) {
    table_trips.destroy();
    $('#trip_liste').empty();
    console.log("Remove #trip_liste");
  }

  // Si pas de trajet à afficher
  trips_number = car_trips.length;
  if (trips_number <1) {
    if ($.fn.DataTable.isDataTable( '#trip_liste' )) {
      $('#trip_liste').DataTable().destroy();
    }
    var x = document.getElementById("div_hist_liste2");
    x.style.display = "none";
    $("#div_hist_liste").empty();
    $("#div_hist_liste").append("Pas de données");
    $("#div_graph_alti").empty();
    return;
  }

  // Affichage des résultats dans le DIV:"div_hist_liste"
  $("#div_hist_liste").empty();
  $("#div_graph_alti").empty();

  // analyse des données pour la création de la table
  var dataSet = [];
  for (i=0; i<trips_number; i++) {
    tmp = car_trips[i].split(',');
    trip_ts_sta = parseInt  (tmp[0],10);  // trip Timestamp start
    trip_ts_end = parseInt  (tmp[1],10);  // trip Timestamp end
    trip_dist   = parseFloat(tmp[2]);     // trip distance
    trip_batt   = parseFloat(tmp[3]);     // trip battery level usage

    var date_ob = new Date(trip_ts_sta*1000);                 // initialize new Date object
    var year = date_ob.getFullYear();                         // year as 4 digits (YYYY)
    var month = ("0" + (date_ob.getMonth() + 1)).slice(-2);   // month as 2 digits (MM)
    var day   = ("0" + date_ob.getDate()).slice(-2);          // daye as 2 digits (DD)
    var hours = ("0" + date_ob.getHours()).slice(-2);         // hours as 2 digits (hh)
    var minutes = ("0" + date_ob.getMinutes()).slice(-2);     // minutes as 2 digits (mm)

    // date & time as YYYY-MM-DD hh:mm:ss format: 
    date_heure_str = day + "-" + month + "-" + year + " /<br> " + hours + " h " + minutes;
    duree = trip_ts_end - trip_ts_sta;
    duree_mn = Math.round(duree/60);
    duree_str = Math.floor(duree_mn/60) + ' h ' + ("0" + duree_mn % 60).slice(-2);
    distance = Math.round(trip_dist*10.0)/10.0;
    vitesse = 3600.0 * distance / duree;
    vitesse = Math.round(vitesse*10.0)/10.0;
    conso = (trip_batt*veh_batt_cap)/100.0;
    line = [date_heure_str, duree_str, distance, vitesse, conso];
    dataSet.push(line);

  }
  //$('#div_hist_liste2').style.display = "block";
  var x = document.getElementById("div_hist_liste2");
  x.style.display = "block";
  table_trips = $('#trip_liste').DataTable( {
      "scrollY": "500px",
      "scrollCollapse": true,
      "searching": false,
      "paging": false,
      data: dataSet,
      select: {
        style: 'single'
      },
      columns: [
          { title: "Date / Heure" },
          { title: "Durée" },
          { title: "Distance (km)" },
          { title: "Vitesse moy.\n(km/h)" },
          { title: "Conso.\n(kWh)" }
      ]
  } );
  
  $('#trip_liste tbody').on('click', 'tr', function () {
    var line = table_trips.row(this).index();
    console.log('You clicked on line:'+line);
    
    if ($(this).hasClass('selected')) {
      $(this).removeClass('selected');
      display_trips(-1);
      $("#div_graph_alti").empty();
      }
      else {
        table_trips.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');
        display_trips(line);
        trip_alti();
      }
  } );
  
}


// gestion du bouton de definition et de mise à jour de la période pour les trajets
// ================================================================================
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
// Cette semaine
$('#btgps_per_this_week').on('click',function(){
  var now = new Date();
  jour_sem = now.getDay();                 // de 0(dim) a 6(sam)
  jour_sem = (jour_sem == 0)?6:jour_sem-1; // de 0(lun) a 6(dim)
  $('#gps_startDate').datepicker( "setDate", (-jour_sem).toString());
  $('#gps_endDate').datepicker( "setDate", (-jour_sem+7).toString());
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

// Fonction d'initialisation de la carte
// =====================================
// On initialise la latitude et la longitude de Paris (centre de la carte)
function initMap(home_lat, home_lon) {
    // met à jour le paramètre CSS associé au DIV
    $('#trips_map').css({height:'800px'});
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

// Ajout des trajets sur la carte
// ==============================
// parametre number: -1 => Affiche tous les trajets, numéro => Affiche uniquement ce trajet
function display_trips(number) {

  // Suppression des trajets existants s'il y en a
  if (trip_polyline.length > 0) {
    for (i=0;i<trip_polyline.length;i++) {
      gps_macarte.removeLayer(trip_polyline[i]);
    }
    trip_polyline = [];
  }
  trips_nb = car_trips.length;
  console.log('car_trips length:'+trips_nb);
  gps_nb   = car_gps.length;
  if ((trips_nb == 0) || (gps_nb == 0) || (number>=trips_nb)) {
    return;
  }
  // generation des objets marqueurs pour les trajets selectionnes
  trip_polyline = [];
  var trip_table = [];
  var trip = [];
  var trip_alti = [];
  var trip_dist = [];
  for (trip_id=0; trip_id<trips_nb; trip_id++) {
    // extract trip infos
    tmp = car_trips[trip_id].split(',');
    trip_ts_sta   = parseInt  (tmp[0],10);  // trip Timestamp start
    trip_ts_end   = parseInt  (tmp[1],10);  // trip Timestamp end
    trip_distance = parseFloat(tmp[2]);     // trip distance
    trip_batt     = parseFloat(tmp[3]);     // trip battery level usage
    trip = [];
    trip_alti = [];
    trip_dist = [];
    for (pts=0; pts<gps_nb; pts++) {
      // extract gps pts infos
      tmp = car_gps[pts].split(',');
      pts_ts   = parseInt  (tmp[0],10);  // Timestamp
      pts_lat  = parseFloat(tmp[1]);     // Lat
      pts_lon  = parseFloat(tmp[2]);     // Lon
      pts_alt  = parseFloat(tmp[3]);     // Altitude
      batt_level  = parseFloat(tmp[4]);     // Battery level
      mileage     = parseFloat(tmp[5]);     // mileage
      moving      = parseInt(tmp[6],10);    // kinetic
      if ((pts_ts>=trip_ts_sta) && (pts_ts<=trip_ts_end)) {
        trip.push([pts_lat, pts_lon]);
        trip_alti.push(pts_alt);
        trip_dist.push(mileage);
      }
    }
    if ((number == -1) || (number == trip_id))
      trip_table.push(trip);
    if (((number == -1)&&(trip_id==0)) || (number == trip_id)) {
      sel_trip_alti = trip_alti;
      sel_trip_dist = trip_dist;
    }
  }

  // affichage des trajets
  if (number == -1) {
    for (trip_id=0; trip_id<trips_nb; trip_id++) {
      trip_polyline[trip_id] = L.polyline(trip_table[trip_id], {color: TRIPS_COLOR_NAMES[trip_id%NB_TRIPS_COLORS], weight: 5, smoothFactor: 1}).addTo(gps_macarte);
    }
  }
  else {
    trip_polyline[0] = L.polyline(trip_table[0], {color: "Red", weight: 5, smoothFactor: 1}).addTo(gps_macarte);
  }
}


// Génération du graphe de relief pour le trajet selectionne
// =========================================================
function trip_alti() {

  // mise en forme des donnnes
  var dt_serie = [[]];
  var dist;
  var dist_deb = sel_trip_dist[0];
  nb_pts = sel_trip_dist.length;
  for (idx=0; idx<nb_pts; idx++) {
    dist = Math.round(10*(sel_trip_dist[idx]-dist_deb))/10;
    dt_serie.push([dist,sel_trip_alti[idx]]);
  }

  // Graphique des distance
  Highcharts.chart('div_graph_alti', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'line'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Distance(km)'
          }
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Altitude(m)'
          }
      },
      tooltip: {
          headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
          pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
              '<td style="padding:0"><b>{point.y:.1f} m</b></td></tr>',
          footerFormat: '</table>',
          shared: true,
          useHTML: true
      },
      series: [{
          name: 'Altitude',
          showInLegend: false,
          color: '#4040FF',
          data: dt_serie

      }]
  });


}

// =======================================================================
//           Gestion des statistiques sur les trajets du véhicule
// =======================================================================

// capturer les donnees depuis le serveur
// ======================================
function loadStats(){
    var param = [];
    globalEqLogic = $("#eqlogic_select option:selected").val();
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getTripStats',
            eqLogic_id: globalEqLogic,  // VIN du vehicule
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadStats] Objet statistique récupéré:" + globalEqLogic);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            stat_data = JSON.parse(data.result);
            console.log("stat_dt:"+data.result);
            trips_stats(stat_data);
        }
    });
}


// Génération du graphe de statistiques pour l'ensemble des trajets
// ================================================================
function trips_stats(stat_data) {

  // mise en forme des donnnes
  var sum_dist = [];
  var dt_dist = [[]];
  var dt_conso = [[]];
  var dt_energy = [[]];
  for (y=2020; y<2040; y++) {
    sum_dist[y] = 0;
    dt_dist[y] = [];
    dt_conso[y] = [];
    dt_energy[y] = [];
    for (m=1; m<=12; m++) {
      // distance
      dt_dist[y][m-1] = 0;
      if (stat_data.dist[y] != null)
        if (stat_data.dist[y][m] != null)
          dt_dist[y][m-1] = stat_data.dist[y][m];
        else
          dt_dist[y][m-1] = 0;
      // Conso
      dt_conso[y][m-1] = 0;
      if (stat_data.dist[y] != null)
        if (stat_data.dist[y][m] != null)
          dt_conso[y][m-1] = Math.round(1000*stat_data.conso[y][m]/stat_data.dist[y][m])/10;
        else
          dt_conso[y][m-1] = 0;
      // Energie
      dt_energy[y][m-1] = 0;
      if (stat_data.dist[y] != null)
        if (stat_data.dist[y][m] != null)
          dt_energy[y][m-1] = stat_data.conso[y][m];
        else
          dt_energy[y][m-1] = 0;
      // somme distance
      sum_dist[y] += dt_dist[y][m-1];
    }
  }
  // console.log("dt_2020:"+dt_dist[2020]);
  // console.log("dt_2021:"+dt_dist[2021]);
  // console.log("sum_dist:"+sum_dist);

  // mise au format attendu par highcharts
  dist_series = [];
  conso_series = [];
  energy_series = [];
  for (y=2020; y<2040; y++) {
    var serie_dist = {
      name: y,
      color: TRIPS_COLOR_NAMES[y-2020],
      data: dt_dist[y]
    };
    var serie_conso = {
      name: y,
      color: TRIPS_COLOR_NAMES[y-2020],
      data: dt_conso[y]
    };
    var serie_energy = {
      name: y,
      color: TRIPS_COLOR_NAMES[y-2020],
      data: dt_energy[y]
    };
    if (sum_dist[y] != 0) {
      dist_series.push(serie_dist);
      conso_series.push(serie_conso);
      energy_series.push(serie_energy);
    }
  }

  // Graphique des distances
  Highcharts.chart('div_graph_stat_dist', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Distance (km)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' km</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: dist_series
  });

  // Graphique de consommation
  Highcharts.chart('div_graph_stat_conso', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Consommation (kWh/100 km)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' kWh/100 km</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: conso_series
  });

  // Energie consommée et cout associé
  var kwh_to_cost = stat_data.cfg_cost_kwh / 0.90;
  Highcharts.chart('div_graph_stat_energy', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Energie (kWh)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' kWh / ' + Math.round(10*point.y*kwh_to_cost)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: energy_series
  });

}


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
  // $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Charactéristiques du véhicule</p>");
  // $("#infos_vehicule").append("<b>Numero VIN:</b>"+info_cars.vin+"<br>");
  // $("#infos_vehicule").append("<b>Numero LCDV:</b>"+info_cars.lcdv+"<br>");
  // $("#infos_vehicule").append("<b>Nom du véhicule:</b> "+info_cars.short_label+"<br>");
  // $("#infos_vehicule").append("<b>Date de début de garantie:</b> "+info_cars.warranty_start_date+"<br>");
  // $("#infos_vehicule").append("<b>Fonctions intégrées:</b> "+info_cars.eligibility+"<br>");
  // $("#infos_vehicule").append("<b>Type:</b> "+info_cars.types+"<br><br>");
 
  // Section valeurs courantes
  // $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Valeurs courantes du véhicule</p>");
  // $("#infos_vehicule").append("<b>Kilométrage courant:</b> "+info_cars.mileage_km+" kms, ");
  // $("#infos_vehicule").append("<b>A la date du :</b> "+info_cars.mileage_ts+"<br><br>");

  // Section version logicielle
  $("#infos_vehicule").append("<p style='font-size: 1.5em;color:Cyan;'>Versions logicielles disponibles</p>");
  
  if (info_cars.rcc_type != null) {
    $("#infos_vehicule").append("<b>LOGICIEL:</b> "+info_cars.rcc_type+"<br>");
    $("#infos_vehicule").append("<b>Version courante connue:</b> "+info_cars.rcc_current_ver+"<br>");
    $("#infos_vehicule").append("<b>Version disponible:</b> "+info_cars.rcc_available_ver+" (datée du "+info_cars.rcc_available_date+")<br>");
    $("#infos_vehicule").append("<b>Taille du fichier:</b> "+Math.round(parseInt(info_cars.rcc_available_size,10)/(1024*1024))+" MB.<br>");
    $("#infos_vehicule").append("<b>Liens de téléchargement:</b><br>");
    $("#infos_vehicule").append('<b> . Fichier =></b>  <a href="'+info_cars.rcc_available_UpURL+'"><b><i>Télécharger</b></i></a><br>');
    $("#infos_vehicule").append('<b> . Licence =></b> <a href="'+info_cars.rcc_available_LiURL+'"><b><i>Télécharger</b></i></a><br><br>');
  }

  if (info_cars.gps_type != null) {
    $("#infos_vehicule").append("<b>LOGICIEL:</b> "+info_cars.gps_type+"<br>");
    $("#infos_vehicule").append("<b>Version courante connue:</b> "+info_cars.gps_current_ver+"<br>");
    $("#infos_vehicule").append("<b>Version disponible:</b> "+info_cars.gps_available_ver+" (datée du "+info_cars.gps_available_date+")<br>");
    $("#infos_vehicule").append("<b>Taille du fichier:</b> "+Math.round(parseInt(info_cars.gps_available_size,10)/(1024*1024))+" MB.<br>");
    $("#infos_vehicule").append("<b>Liens de téléchargement:</b><br>");
    $("#infos_vehicule").append('<b> . Fichier =></b>  <a href="'+info_cars.gps_available_UpURL+'"><b><i>Télécharger</b></i></a><br>');
    $("#infos_vehicule").append('<b> . Licence =></b> <a href="'+info_cars.gps_available_LiURL+'"><b><i>Télécharger</b></i></a><br><br>');
  }

  if (info_cars.map_type != null) {
    $("#infos_vehicule").append("<b>LOGICIEL:</b> "+info_cars.map_type+"<br>");
    $("#infos_vehicule").append("<b>Version courante connue:</b> "+info_cars.map_current_ver+"<br>");
    $("#infos_vehicule").append("<b>Version disponible:</b> "+info_cars.map_available_ver+" (datée du "+info_cars.map_available_date+")<br>");
    $("#infos_vehicule").append("<b>Taille du fichier:</b> "+Math.round(parseInt(info_cars.map_available_size,10)/(1024*1024))+" MB.<br>");
    $("#infos_vehicule").append("<b>Liens de téléchargement:</b><br>");
    $("#infos_vehicule").append('<b> . Fichier =></b>  <a href="'+info_cars.map_available_UpURL+'"><b><i>Télécharger</b></i></a><br>');
    $("#infos_vehicule").append('<b> . Licence =></b> <a href="'+info_cars.map_available_LiURL+'"><b><i>Télécharger</b></i></a><br>');
  }
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
