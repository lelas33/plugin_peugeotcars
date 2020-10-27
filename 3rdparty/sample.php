<?php
require_once("peugeotcars_api.class.php");

$session_peugeotcars = new peugeotcars_api_v1();

$vin = "VF30A9HR8BS331949";
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

print("Test login sequence:\n");
print("====================\n");
$session_peugeotcars->pg_full_login();
print("\n");



?>
