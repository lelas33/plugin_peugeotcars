
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


// Variables partagées
var gps_macarte = null;
var marker_veh = null;
var marker_home = null;
var vin = "";

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
$(document).ready(function() {
  vin = $('#veh_vin').val();
  console.log("VIN:"+vin);
  get_current_position();
});

// Fonction d'initialisation de la carte
// =====================================
function initMap(lat_home, lon_home, lat_veh, lon_veh) {
    // met à jour le paramètre CSS associé au DIV
    $('#trips_map').css({height:'800px'});
    // Créer l'objet "gps_macarte" et l'insèrer dans l'élément HTML qui a l'ID "trips_map"
    gps_macarte = L.map('trips_map').setView([lat_veh, lon_veh], 11);
    // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        // Il est toujours bien de laisser le lien vers la source des données
        attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
        minZoom: 1,
        maxZoom: 20
    }).addTo(gps_macarte);
    // Add marker home
    var iconOptions = {iconUrl: 'plugins/peugeotcars/3rdparty/leaflet_v1.7.1/home_icon.png', iconSize: [40, 40]};
    // Creating a custom icon
    var customIcon = L.icon(iconOptions);
    // Creating Marker Options
    var markerOptions = {title: "Maison", clickable: true, draggable: true, icon: customIcon};
    if (marker_home != null)
      carte.removeLayer(marker_home);
    marker_home = L.marker([lat_home, lon_home], markerOptions).addTo(gps_macarte);
    // Add marker veh
    if (marker_veh != null)
      carte.removeLayer(marker_veh);
    marker_veh = L.marker([lat_veh, lon_veh], {title: "Voiture"}).addTo(gps_macarte);
    // Add button
    L.easyButton( '<span style="font-size: 1.5em;">&starf;</span>', function(){
//      alert("Click button");
      get_current_position();
    }).addTo(gps_macarte);
}

// Fonction de mise à jour de la carte
// ====================================
function updateMap(lat_veh, lon_veh) {
    // Update marker veh
    if (marker_veh != null)
      marker_veh.setLatLng([lat_veh, lon_veh]);
}

// capturer les donnees depuis le serveur
// ======================================
function get_current_position(){
    if (vin == "")
      return;
    $.ajax({
        type: 'POST',
        url: 'plugins/peugeotcars/core/ajax/peugeotcars.ajax.php',
        data: {
            action: 'getCurrentPosition',
            eqLogic_id: vin   // VIN du vehicule
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[get_current_position] Objet peugeotcars récupéré : " + vin);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            CurrentPosition = JSON.parse(data.result);
            // recopie les donnees recues (trajets et points GPS)
            // coordonnees domicile
            tmp = CurrentPosition.veh.split(',');
            lat_veh = parseFloat(tmp[0]);
            lon_veh = parseFloat(tmp[1]);
            tmp = CurrentPosition.home.split(',');
            lat_home = parseFloat(tmp[0]);
            lon_home = parseFloat(tmp[1]);
            if (gps_macarte == null)
              initMap(lat_home, lon_home, lat_veh, lon_veh);
            else
              updateMap(lat_veh, lon_veh);
        }
    });
}
