<?php
require_once("peugeotcars_api3.class.php");


$vin  = "Vxxx";

$username = "";
$password = "";

print("Login API PSA:\n");
print("==============\n");
$session_peugeotcars = new peugeotcars_api3();
$session_peugeotcars->login($username, $password, NULL);
$login = $session_peugeotcars->pg_api_login();
var_dump($login);
print("\n");


print("ID vehicules:\n");
print("=============\n");
$ret = $session_peugeotcars->pg_api_vehicles($vin);
var_dump($ret);
print("\n");

print("Status vehicules:\n");
print("=================\n");
$ret = $session_peugeotcars->pg_api_vehicles_status();
var_dump($ret);
print("\n");


// print("Trajets vehicules:\n");
// print("=================\n");
// $session_peugeotcars->pg_api_vehicles_trips();
// print("\n");

// print("Alertes vehicules:\n");
// print("==================\n");
// $session_peugeotcars->pg_api_vehicles_alerts();
// print("\n");

// print("Position GPS vehicules:\n");
// print("=======================\n");
// $session_peugeotcars->pg_api_vehicles_last_position();
// print("\n");

// print("Telemetrie vehicules:\n");
// print("=====================\n");
// $session_peugeotcars->pg_api_vehicles_telemetry();
// print("\n");

// print("Maintenance vehicules:\n");
// print("======================\n");
// $session_peugeotcars->pg_api_vehicles_maintenance();
// print("\n");

 // print("Update logiciels:\n");
 // print("=================\n");
 // $session_peugeotcars->pg_api_sw_updates($vin, "rcc-firmware");
 // print("\n");
// $session_peugeotcars->pg_api_sw_updates($vin, "ovip-int-firmware-version");
// print("\n");
// $session_peugeotcars->pg_api_sw_updates($vin, "map-eur");
// print("\n");

// print("AP MYM User:\n");
// print("============\n");
// $session_peugeotcars->pg_ap_mym_user();
// print("\n");

// print("AP MYM Maintenance:\n");
// print("===================\n");
// $session_peugeotcars->pg_ap_mym_maintenance($vin);
// print("\n");





?>
