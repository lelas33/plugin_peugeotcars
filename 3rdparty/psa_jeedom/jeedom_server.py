# Serveur vers le plugin Jeedom PeugeotCars

import socket
from MyLogger import my_logger
from MyLogger import logger

PORT = 65432
# Port to listen on (non-privileged ports are > 1023)
MSG_PAYLOAD_SIZE = 128  # Taille message = 128 bytes

CMD_PRECOND   = 0x10   # préconditionnement du véhicule
CMD_CHARGING  = 0x20   # Recharge de la batterie
CMD_WAKEUP    = 0x30   # Reveil du vehicule
CMD_GET_STATE = 0x40   # Etat du vehicule



class my_jeedom_server:
    def __init__(self, myp, vin):
        self.myp = myp
        self.vin = vin
        self.port = PORT
        self.cmd_msg = [0]*MSG_PAYLOAD_SIZE
        self.ack_msg = [0]*MSG_PAYLOAD_SIZE
        self.last_mc_cmd = 0
        self.last_mc_nbp = 0
        self.last_param0 = 0
        self.last_param1 = 0
        self.last_param2 = 0

    def server_loop(self):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
#            s.bind((socket.gethostname(), self.port))
            s.bind(('localhost', self.port))
            s.listen()
            while True:
                (conn, addr) = s.accept()
                with conn:
                    print('Connected by', addr)
                    self.cmd_msg = conn.recv(MSG_PAYLOAD_SIZE)
                    self.msg_analyze_cmd()
                    conn.sendall(bytes(self.ack_msg))

    def msg_analyze_cmd(self):
        # Analyse message reçu
        mc_cmd = self.cmd_msg[0] + 256*self.cmd_msg[1]
        mc_nbp = self.cmd_msg[2] + 256*self.cmd_msg[3]
        param0 = self.cmd_msg[4] if (mc_nbp >= 1) else 0
        param1 = self.cmd_msg[5] if (mc_nbp >= 2) else 0
        param2 = self.cmd_msg[6] if (mc_nbp >= 3) else 0

        logger.info("Commande reçue:"+hex(mc_cmd))
        # traitement commande
        self.msg_execute_cmd(mc_cmd, mc_nbp, param0, param1, param2)

        # genere message ack
        ma_cmd = mc_cmd | 0x8000
        ma_nbp = 1
        self.ack_msg = [0] * MSG_PAYLOAD_SIZE
        self.ack_msg[0] = ma_cmd & 0xff
        self.ack_msg[1] = (ma_cmd >> 8) & 0xff
        self.ack_msg[2] = ma_nbp & 0xff
        self.ack_msg[3] = (ma_nbp >> 8) & 0xff
        self.ack_msg[4] = self.cmd_msg[4]
        
        # memorise command si besoin de retry
        self.last_mc_cmd = mc_cmd
        self.last_mc_nbp = mc_nbp
        self.last_param0 = param0
        self.last_param1 = param1
        self.last_param2 = param2

    def msg_execute_cmd(self, mc_cmd, mc_nbp, param0, param1, param2):
        # traitement commande
        if (mc_cmd == CMD_PRECOND): # préconditionnement du véhicule
            if (param0 == 1):
                # activation préconditionnement
                logger.info("Activation préconditionnement")
                precond = True
            else:
                # arret préconditionnement
                logger.info("Arrêt préconditionnement")
                precond = False
            self.myp.preconditioning(self.vin, precond)

        elif (mc_cmd == CMD_CHARGING):  # Recharge de la batterie
            charge_type = "immediate" if (param0 == 1) else "delayed"
            hour   = param1 % 24
            minute = param2 % 60
            self.myp.veh_charge_request(self.vin, hour, minute, charge_type)

        elif (mc_cmd == CMD_WAKEUP):    # Reveil du vehicule
            self.myp.wakeup(self.vin)

        elif (mc_cmd == CMD_GET_STATE): # Etat du vehicule
            self.myp.get_state(self.vin)

    def msg_resend_last_cmd(self):
        # renvoi de la derniere commande
        self.msg_execute_cmd(self.last_mc_cmd, self.last_mc_nbp, self.last_param0, self.last_param1, self.last_param2)
