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
define("CMD_GET_STATE_RD",  0x41);
define("CMD_GET_STATE_ALT", 0x42);   // Etat du vehicule a partir des infos issues du serveur MQTT

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
  // $tab_param  = '';
  // $tab_param .= chr(0xff);
  // $tab_param .= chr(0x00);
  // $tab_param .= chr(0x00);
  // $tab_param .= chr(0xff);
  // socket_send ( $socket, $tab_param, 4, 0 ) ;

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

  // Attente de l'acquittement: message de 128 octets Ã©galement
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

// Send message using TCP/IP com
// -----------------------------
function mqtt_message_send2($socket, $cmd, $msg_json, &$cmd_ack)
{
  // Longueur du Message a envoyer
  $msg_len =  strlen($msg_json);

  // Entete du message: taille fixe de 8 octets
  // 0xff,0xf0, cmd(MSB),cmd(LSB), msg_payload_len(MSB),msg_payload_len(LSB), 0x0f,0xff
  $tab_param = str_repeat(chr(0xff), 8);
  $tab_param[1] = chr(0xf0);
  $tab_param[2] = chr((($cmd) & 0xff00) >> 8);
  $tab_param[3] = chr(( $cmd) & 0x00ff);
  $tab_param[4] = chr((($msg_len) & 0xff00) >> 8);
  $tab_param[5] = chr(( $msg_len) & 0x00ff);
  $tab_param[6] = chr(0x0f);

  // Envoi de l'entete du message
  socket_send ( $socket, $tab_param, 8, 0) ;

  // envoi du corps du message avec les parametres
  socket_send ( $socket, $msg_json, $msg_len, 0) ;

  // Attente de l'acquittement: entete du message de retour de 8 octets egalement
  $lg = socket_recv ($socket, $tab_param, 8, MSG_WAITALL) ;

  if (($lg == 8) && (ord($tab_param[2]) == (($cmd & 0xff00) >> 8)) && (ord($tab_param[3]) == ($cmd & 0x00ff)) &&
     (ord($tab_param[0]) == 0xff) && (ord($tab_param[1]) == 0xf0) && (ord($tab_param[6]) == 0x0f) && (ord($tab_param[7]) == 0xff)) {
     // entete de retour correct
     $ack_len = (ord($tab_param[4]) << 8) | (ord($tab_param[5]));
     }
   else {
     return (0);  // Erreur entete message retour incorrect
     }

  // corps du message de retour
  $tab_param = "";
  $lg = socket_recv ($socket, $tab_param, $ack_len, MSG_WAITALL);
  // log::add('peugeotcars','debug',"Retour mqtt:".$tab_param);
  $cmd_ack = json_decode($tab_param);
  if ($lg == $ack_len)
    return (1);
  else
    return (0);  // Erreur longueur message retour incorrecte
}


?>
