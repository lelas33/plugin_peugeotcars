<?php
require_once("peugeotcars_api2.class.php");


$vin  = "Vxxxxxxxxxxxxxxxx";

$username = "xx.yy@zz.fr";
$password = "xxxxxxxx";

// print("Login API PSA Step1:\n");
// print("====================\n");
// $session_peugeotcars->login($username, $password);
// $session_peugeotcars->pg_api_login1();
// print("\n");

// print("Login API PSA Step2:\n");
// print("====================\n");
// $session_peugeotcars->pg_api_login2();
// print("\n");

// print("Login API PSA Step3:\n");
// print("====================\n");
// $session_peugeotcars->pg_api_login3();
// print("\n");

print("Login API PSA Step1&2:\n");
print("======================\n");
$session_peugeotcars = new peugeotcars_api_v2();
$session_peugeotcars->login($username, $password, NULL);
$login = $session_peugeotcars->pg_api_login1_2();   // Authentification

print("ID vehicules:\n");
print("=============\n");
$session_peugeotcars->pg_api_vehicles($vin);
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
