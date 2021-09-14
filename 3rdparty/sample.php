<?php
require_once("peugeotcars_api3.class.php");

$vin     = "Vxxxxxxxxxxxxxxxx";
$brandid = "AP";           // Peugeot => "AP" ,  Citroën => "AC"  ,  Citroën-DS => "DS"  ,  Opel => "OP"  ,  Vauxhall => "VX"

$username = "xx.yy@zz.fr";
$password = "xxxxxxxx";

$session_peugeotcars = new peugeotcars_api3();

print("Login API PSA:\n");
print("==============\n");
$session_peugeotcars->login($username, $password, $brandid, NULL);
$login = $session_peugeotcars->pg_api_login();
var_dump($login);
print("\n");

print("ID vehicules:\n");
print("=============\n");
$ret = $session_peugeotcars->pg_api_vehicles($vin);
var_dump($ret);
print("\n");

$session_peugeotcars->set_debug_api();

print("Status vehicules:\n");
print("=================\n");
$ret = $session_peugeotcars->pg_api_vehicles_status();
var_dump($ret);
print("\n");

// print("Status preconditionnement:\n");
// print("==========================\n");
// $ret = $session_peugeotcars->pg_api_vehicles_precond();
// var_dump($ret);
// print("\n");


// print("Test API vehicules:\n");
// print("===================\n");
// $session_peugeotcars->pg_api_vehicles_test();
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
// $ret = $session_peugeotcars->pg_api_sw_updates($vin, "rcc-firmware");
// print("\n");
// var_dump($ret);
// $ret = $session_peugeotcars->pg_api_sw_updates($vin, "ovip-int-firmware-version");
// print("\n");
// var_dump($ret);
// $ret = $session_peugeotcars->pg_api_sw_updates($vin, "map-eur");
// print("\n");
// var_dump($ret);

// $session_peugeotcars->set_debug_api();


// print("Login API MYM :\n");
// print("===============\n");
// $login = $session_peugeotcars->pg_api_mym_login();
// var_dump($login);
// print("\n");

// print("AP MYM Maintenance:\n");
// print("===================\n");
// $ret = $session_peugeotcars->pg_ap_mym_maintenance($vin);
// var_dump($ret);
// print("\n");


// print("AP MYM User:\n");
// print("============\n");
// $session_peugeotcars->pg_ap_mym_user();
// print("\n");

?>

