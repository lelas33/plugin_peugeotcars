<?php
require_once("peugeotcars_api.class.php");

$session_peugeotcars = new peugeotcars_api_v1();

$vin = "VF30A9HR8BS331949";  // 5008
//$vin = "VR3UHZKXZLT075235";    // e208
// print("Client ID:\n");
// print("==========\n");
// var_dump($session_peugeotcars->pg_get_client_id());
// print("\n");

// print("Appli messages:\n");
// print("===============\n");
// var_dump($session_peugeotcars->pg_get_appli_msg());
// print("\n");

// print("Liste des vendeurs favoris:\n");
// print("===========================\n");
// var_dump($session_peugeotcars->pg_get_favorite_dealers());
// print("\n");

// print("Test login sequence:\n");
// print("====================\n");
// $session_peugeotcars->pg_full_login();
// print("\n");

// print("Test user data:\n");
// print("====================\n");
// $session_peugeotcars->pg_api_me_vehicules($vin);
// print("\n");

// print("Test user data:\n");
// print("====================\n");
// $session_peugeotcars->pg_api_me_user();
// print("\n");

// print("Vendeur favori:\n");
// print("===============\n");
// $session_peugeotcars->pg_api_me_favorite_dealers();
// print("\n");

// print("Profil utilisateur:\n");
// print("===================\n");
// $session_peugeotcars->pg_api_me_profile_data();
// print("\n");

print("Maintenance vehicule:\n");
print("=====================\n");
$session_peugeotcars->pg_api_me_vehicules_maintenance($vin);
print("\n");

// print("Data vehicule:\n");
// print("==============\n");
// $session_peugeotcars->pg_api_me_vehicules_data($vin);
// print("\n");

// print("WS parameters:\n");
// print("==============\n");
// $session_peugeotcars->pg_api_me_ws_parameters();
// print("\n");

// print("Update logiciels:\n");
// print("=================\n");
// $session_peugeotcars->pg_api_sw_updates($vin, "rcc-firmware");
// print("\n");
// $session_peugeotcars->pg_api_sw_updates($vin, "ovip-int-firmware-version");
// print("\n");
// $session_peugeotcars->pg_api_sw_updates($vin, "map-eur");
// print("\n");

// print("Info voiture (api car):\n");
// print("=======================\n");
// $session_peugeotcars->pg_api_car_vehicule($vin);
// print("\n");

// print("Info SW RCC (api car):\n");
// print("======================\n");
// $session_peugeotcars->pg_api_car_rcc_sw($vin);
// print("\n");

// print("Info SW NAC (api car):\n");
// print("======================\n");
// $session_peugeotcars->pg_api_car_nac_sw($vin);
// print("\n");



?>
