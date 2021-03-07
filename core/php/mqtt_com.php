<?php

// Communication with Python MQTT link
// ===================================


// definition des addresses IP des peripheriques
// =============================================
//define('HOST_MQTT_IP', "192.168.1.11");
define('HOST_MQTT_IP', '127.0.0.1');
define('HOST_MQTT_PO', 65432);

// commandes vers le vehicule
define("CMD_PRECOND",       0x10);
define("CMD_PRECOND_PROGS", 0x11);
define("CMD_CHARGING",      0x20);
define("CMD_WAKEUP",        0x30);
define("CMD_GET_STATE",     0x40);

// Open socket
// -----------
function mqtt_start_socket ()
{
  // Creation d'une liaison TCP/IP avec le serveur
  //----------------------------------------------
  // Creation d'un socket
  $socket = socket_create(AF_INET, SOCK_STREAM, 0) ;
  //echo "  Socket : $socket<br>" ;

  // connect to socket
  $host = HOST_MQTT_IP;
  $port = HOST_MQTT_PO;

  $err = socket_connect($socket, $host, $port) ;
  //echo "  Connect erreur : $err<br>" ;
  return $socket;
}

// Close socket
// ------------
function mqtt_end_socket ($socket)
{

  // Envoi message deconnection
  $tab_param  = '';
  $tab_param .= chr(0xff);
  $tab_param .= chr(0x00);
  $tab_param .= chr(0x00);
  $tab_param .= chr(0xff);
  socket_send ( $socket, $tab_param, 4, 0 ) ;

  // Fermeture socket
  socket_shutdown($socket, 2) ;
  socket_close($socket) ;

}

// Send message using TCP/IP com
// -----------------------------
function mqtt_message_send($socket, $msg, &$ack)
{

  $lg_mess = 128;

  // Preparation du message
  $tab_param = str_repeat(chr(32),$lg_mess);   // longueur trame message max : 2 + 2 + 124

  $tab_param[0] = chr(( $msg['cmd']) & 0x000000ff);
  $tab_param[1] = chr((($msg['cmd']) & 0x0000ff00) >> 8);
  $tab_param[2] = chr(( $msg['nbp']) & 0x000000ff);
  $tab_param[3] = chr((($msg['nbp']) & 0x0000ff00) >> 8);
  if ($msg['nbp'] != 0) {
    if ($msg['nbp'] >= 124) $msg['nbp'] = 124;
    for ($i = 0; $i<$msg['nbp']; $i++) {
      $tab_param[4+$i] = chr($msg['param'][$i]);
      }
    }

  // Envoi du message
  socket_send ( $socket, $tab_param, $lg_mess, 0) ;

  // Attente de l'acquittement: message de 128 octets également
  $lg = socket_recv ($socket, $tab_param, 128, MSG_WAITALL) ;

  // Mise en forme du message de retour
  $ack['cmd'] = (ord($tab_param[1]) << 8) | ord($tab_param[0]);
  $ack['nbp'] = (ord($tab_param[3]) << 8) | ord($tab_param[2]);
  if ($ack['nbp'] != 0) {
    if ($ack['nbp'] >= 124) $ack['nbp'] = 124;
    for ($i=0; $i<$ack['nbp']; $i++)
       $ack['param'][$i] = ord($tab_param[$i+4]);
    }

  // Verification format message retour
  if ((($ack['cmd'] & 0x8000) == 0x8000) && (($ack['cmd'] & 0x7FFF) == ($msg['cmd'] & 0x7FFF)))
    $ack['status'] = "OK";
  else
    $ack['status'] = "KO";
 }



?>
