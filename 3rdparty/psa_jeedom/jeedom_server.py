# Serveur vers le plugin Jeedom PeugeotCars

import socket
from MyLogger import my_logger
from MyLogger import logger

PORT = 65432
# Port to listen on (non-privileged ports are > 1023)
MSG_PAYLOAD_SIZE = 128             # Taille message = 128 bytes
MSG_MAX_SIZE = MSG_PAYLOAD_SIZE-4  # Max Taille message utile

CMD_PRECOND       = 0x10   # préconditionnement du véhicule
CMD_PRECOND_PROGS = 0x11   # Programmes de préconditionnement
CMD_CHARGING      = 0x20   # Recharge de la batterie
CMD_WAKEUP        = 0x30   # Reveil du vehicule
CMD_GET_STATE     = 0x40   # Etat du vehicule



class my_jeedom_server:
    def __init__(self, myp, vin):
        self.myp = myp
        self.vin = vin
        self.port = PORT
        self.cmd_msg = [0]*MSG_PAYLOAD_SIZE
        self.ack_msg = [0]*MSG_PAYLOAD_SIZE
        self.last_mc_cmd = 0
        self.last_mc_nbp = 0
        self.last_params = [0]*MSG_MAX_SIZE

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
        params = [0]*MSG_MAX_SIZE
        # Analyse message reçu
        mc_cmd = self.cmd_msg[0] + 256*self.cmd_msg[1]
        mc_nbp = self.cmd_msg[2] + 256*self.cmd_msg[3]
        if mc_nbp>MSG_MAX_SIZE:
            mc_nbp = MSG_MAX_SIZE
        for id in range(mc_nbp):
            params[id] = self.cmd_msg[id+4]

        logger.info("Commande reçue:"+hex(mc_cmd))
        # traitement commande
        self.msg_execute_cmd(mc_cmd, mc_nbp, params)

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
        self.last_params = params

    def msg_execute_cmd(self, mc_cmd, mc_nbp, params):
        # traitement commande
        if (mc_cmd == CMD_PRECOND): # préconditionnement du véhicule
            if (params[0] == 1):
                # activation préconditionnement
                logger.info("Activation préconditionnement")
                precond = True
            else:
                # arret préconditionnement
                logger.info("Arrêt préconditionnement")
                precond = False
            self.myp.preconditioning(self.vin, precond)

        elif (mc_cmd == CMD_PRECOND_PROGS):  # Programmes de préconditionnement
            progs = { 
                "program1": {"day": [params[ 3], params[ 4], params[ 5], params[ 6], params[ 7], params[ 8], params[ 9]], "hour": params[ 1], "minute": params[ 2], "on": params[ 0]},
                "program2": {"day": [params[13], params[14], params[15], params[16], params[17], params[18], params[19]], "hour": params[11], "minute": params[12], "on": params[10]},
                "program3": {"day": [params[23], params[24], params[25], params[26], params[27], params[28], params[29]], "hour": params[21], "minute": params[22], "on": params[20]},
                "program4": {"day": [params[33], params[34], params[35], params[36], params[37], params[38], params[39]], "hour": params[31], "minute": params[32], "on": params[30]}}
            self.myp.preconditioning_progs(self.vin, progs)

        elif (mc_cmd == CMD_CHARGING):  # Recharge de la batterie
            charge_type = "immediate" if (params[0] == 1) else "delayed"
            hour   = params[1] % 24
            minute = params[2] % 60
            self.myp.veh_charge_request(self.vin, hour, minute, charge_type)

        elif (mc_cmd == CMD_WAKEUP):    # Reveil du vehicule
            self.myp.wakeup(self.vin)

        elif (mc_cmd == CMD_GET_STATE): # Etat du vehicule
            self.myp.get_state(self.vin)

    def msg_resend_last_cmd(self):
        # renvoi de la derniere commande
        self.msg_execute_cmd(self.last_mc_cmd, self.last_mc_nbp, self.last_params)
