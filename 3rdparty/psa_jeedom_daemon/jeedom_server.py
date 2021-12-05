# Serveur vers le plugin Jeedom PeugeotCars

import socket
import json
import time
from psa_car_controller.mylogger import my_logger
from psa_car_controller.mylogger import logger

PORT = 65432
# Port to listen on (non-privileged ports are > 1023)
MSG_HEADER_SIZE  =   8             # Taille header message = 8 bytes
MSG_PAYLOAD_SIZE = 128             # Taille message = 128 bytes
MSG_MAX_SIZE = MSG_PAYLOAD_SIZE-4  # Max Taille message utile

CMD_PRECOND       = 0x0010   # préconditionnement du véhicule
CMD_PRECOND_PROGS = 0x0011   # Programmes de préconditionnement
CMD_CHARGING      = 0x0020   # Recharge de la batterie
CMD_WAKEUP        = 0x0030   # Reveil du vehicule
CMD_GET_STATE     = 0x0040   # Etat du vehicule
CMD_GET_STATE_RD  = 0x0041   # Etat du vehicule (retour donnees uniquement)
CMD_GET_STATE_ALT = 0x0042   # Etat du vehicule a partir des infos issues du serveur MQTT



class my_jeedom_server:
    def __init__(self, myp, vin):
        self.myp = myp
        self.vin = vin
        self.port = PORT
        self.cmd_hdr = []     # buffer reception entete message
        self.cmd_msg = []     # buffer reception corps message
        self.ack_hdr = []     # buffer emission entete message retour
        self.ack_msg = []     # buffer emission corps message retour
        self.msg_size   = 0
        self.cmd        = 0
        self.cmd_nbp    = 0
        self.cmd_params = []
        self.ack_params = []
        self.last_cmd    = 0
        self.last_nbp    = 0
        self.last_params = []
        self.last_ts = 0

    def server_loop(self):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
#            s.bind((socket.gethostname(), self.port))
            s.bind(('localhost', self.port))
            s.listen()
            while True:
                (conn, addr) = s.accept()
                with conn:
                    # print('Connected by', addr)
                    # reception entete
                    # print('Get message header')
                    self.cmd_hdr = conn.recv(MSG_HEADER_SIZE)
                    ctr_hdr = self.msg_analyze_header()
                    
                    # reception corps message
                    if ctr_hdr == 0:
                        print('Error in message header received')
                    else:
                        # print('Get message payload')
                        self.cmd_msg = conn.recv(self.msg_size)
                        # print('message received:', self.cmd,' and payload received:', self.cmd_msg)
                        # logger.info("Analyse commande: %x", self.cmd)
                        ctr_msg = self.msg_analyze_msg()
                        # logger.info("Traite commande: %x", self.cmd)
                        ctr_pro = self.msg_execute_cmd(self.cmd, self.cmd_nbp, self.cmd_params)
                        # logger.info("Renvoi ACK: %x", self.cmd)
                        ctr_ack = self.msg_generate_ack()
                        # envoi entete message retour
                        conn.sendall(bytes(self.ack_hdr))
                        # envoi corps message retour
                        conn.sendall(bytes(self.ack_msg, 'utf-8'))

    # Analyse de l'entete du message
    def msg_analyze_header(self):
        if (len(self.cmd_hdr) == 8) and (self.cmd_hdr[0] == 0xff) and (self.cmd_hdr[1] == 0xf0) and (self.cmd_hdr[6] == 0x0f) and (self.cmd_hdr[7] == 0xff):
            self.cmd       = ((self.cmd_hdr[2]) <<8) | (self.cmd_hdr[3])
            self.msg_size  = ((self.cmd_hdr[4]) <<8) | (self.cmd_hdr[5])
            return 1
        else:
            return 0
    # analyse des parametres de la commande
    def msg_analyze_msg(self):
        self.cmd_params = json.loads(self.cmd_msg)
        try:
            self.cmd_nbp = len(self.cmd_params["param"])
            # memorise command si besoin de retry
            if (self.cmd != CMD_GET_STATE_ALT):
                self.last_cmd = self.cmd
                self.last_nbp = self.cmd_nbp
                self.last_params = self.cmd_params
                self.last_ts = time.time()
        except:
            self.cmd_nbp = 0
            # memorise command si besoin de retry
            if (self.cmd != CMD_GET_STATE_ALT):
                self.last_cmd = self.cmd
                self.last_nbp = 0
                self.last_params = []
                self.last_ts = time.time()
        # print('Nb Params received', self.cmd_nbp)
        return 1

    def msg_execute_cmd(self, mc_cmd, mc_nbp, mc_param):
        self.ack_params = {}
        # traitement commande
        if (mc_cmd == CMD_PRECOND): # préconditionnement du véhicule
            if (mc_param["param"][0] == 1):
                # activation préconditionnement
                logger.info("Activation préconditionnement")
                precond = True
            else:
                # arret préconditionnement
                logger.info("Arrêt préconditionnement")
                precond = False
            self.myp.preconditioning(self.vin, precond)

        elif (mc_cmd == CMD_PRECOND_PROGS):  # Programmes de préconditionnement
            progs = self.cmd_params            
            self.myp.preconditioning_progs(self.vin, progs)

        elif (mc_cmd == CMD_CHARGING):  # Recharge de la batterie
            charge_type = "immediate" if (mc_param["param"][0] == 1) else "delayed"
            hour   = mc_param["param"][1] % 24
            minute = mc_param["param"][2] % 60
            self.myp.veh_charge_request(self.vin, hour, minute, charge_type)

        elif (mc_cmd == CMD_WAKEUP):    # Reveil du vehicule
            self.myp.wakeup(self.vin)

        elif (mc_cmd == CMD_GET_STATE): # Etat du vehicule
            self.myp.fatal_error = 1
            
        elif (mc_cmd == CMD_GET_STATE_RD): # Etat du vehicule (retour donnees uniquement)
            self.ack_params = self.myp.last_state

        elif (mc_cmd == CMD_GET_STATE_ALT): # Etat du vehicule Alternatif
            self.ack_params["trip_in_progress"] = self.myp.trip_in_progress
            self.ack_params["signal_quality"] = self.myp.mem_state["signal_quality"]
            self.ack_params["reason"] = self.myp.mem_state["reason"]
            self.ack_params["sev_state"] = self.myp.mem_state["sev_state"]
            # logger.info("trip_in_progress: %d", self.ack_params["trip_in_progress"])
            # logger.info("signal_quality:   %s", self.ack_params["signal_quality"])
            # logger.info("reason:           %s", self.ack_params["reason"])
            # logger.info("sev_state:        %s", self.ack_params["sev_state"])

    def msg_generate_ack(self):
        # genere ack entete et corps message
        self.ack_msg = json.dumps(self.ack_params)
        ack_len = len(self.ack_msg)
        self.ack_hdr = [0xff]*8
        self.ack_hdr[1] = 0xf0
        self.ack_hdr[2] = (self.cmd >> 8) & 0xff
        self.ack_hdr[3] = self.cmd & 0xff
        self.ack_hdr[4] = (ack_len >> 8) & 0xff
        self.ack_hdr[5] = ack_len & 0xff
        self.ack_hdr[6] = 0x0f

    def msg_resend_last_cmd(self):
        # renvoi de la derniere commande (si pas CMD_GET_STATE_ALT, et si pas plus vielle que 15 mn)
        cmd_age = int(time.time() - self.last_ts);
        if (self.last_cmd != CMD_GET_STATE_ALT) and (cmd_age < 15*60):
            logger.info("Relance la commande: %x", self.last_cmd)
            self.msg_execute_cmd(self.last_cmd, self.last_nbp, self.last_params)
