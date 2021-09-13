#!/usr/bin/env python3
import atexit
import sys
import os
import time
from oauth2_client.credentials_manager import OAuthError
from threading import Thread
import argparse
import base64

dossier = os.path.dirname(os.path.abspath(__file__))
dossier = dossier+'/psa_car_controller'
if dossier not in sys.path:
    sys.path.append(dossier)

from psa_car_controller.mylogger import my_logger
from psa_car_controller.mylogger import logger
from psa_car_controller.my_psacc import MyPSACC

import jeedom_server

parser = argparse.ArgumentParser()


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument("-d", "--debug", help="enable debug", const=10, default=20, nargs='?', metavar='Debug level number')
    parser.add_argument("-m", "--param", help="configuration parameters")
    parser.add_argument("-b", "--base-path", help="base path for plugin",default="/")
    parser.parse_args()
    return parser

myp = None

if __name__ == "__main__":
    if sys.version_info < (3, 6):
        raise RuntimeError("This application requires Python 3.6+")
    parser = parse_args()
    args = parser.parse_args()
    my_logger(handler_level=args.debug)
    logger.info("server start")
    os.chdir(args.base_path)
    logger.info("Current directory:"+os.getcwd())
    # get configuration parameters
    param = args.param
    params = base64.b64decode(param).decode('utf-8')
    #logger.info("param:"+param)
    #logger.info("params:"+params)
    param_set = params.split(",")
    #logger.info("account:" +param_set[0])
    #logger.info("password:"+param_set[1])
    #logger.info("sms code:"+param_set[2])
    #logger.info("pin code:"+param_set[3])
    myp = MyPSACC.load_config("config.json")
    myp.set_codes(param_set[2], param_set[3])
    myp.flog_mqtt = open("log_mqtt.log", "a")
    atexit.register(myp.save_config)
    try:
        myp.manager._refresh_token()
    except OAuthError:
        if param_set[0] and param_set[1]:
            client_email = param_set[0]
            client_password = param_set[1]
            myp.connect(client_email, client_password)
        else:
            logger.error("Mail and Password undefined")
    my_veh_list = myp.get_vehicles()
    logger.info(my_veh_list)
    vin = my_veh_list[0].vin
    logger.info("VIN:"+vin)
    logger.info("MQTT link start")
    myp.start_mqtt()

    # Start communication with Jeedom plugin using TCP socket (@localhost)
    logger.info("Jeedom link start")
    js = jeedom_server.my_jeedom_server(myp, vin)
    # run the main loop for jeedom link
    t1 = Thread(target=js.server_loop)
    t1.start()

    # Monitoring for MQTT server: (1 check every 0.5 minute)
    #  * "fatal error" of MQTT server, in order to restart it if necessary 
    #  * Message not processed due to token expired
    while True:
        time.sleep(30)
        if myp.resend_command == 1:
            logger.info("Token expired error detected => Resend last command")
            js.msg_resend_last_cmd()
            myp.resend_command = 0
        #if myp.fatal_error == 1:
            # logger.info("Fatal error detected => Restart MQTT link")
            # exit demon, in order to be restarted by Jeedom monitoring
            # sys.exit(1)
            # myp.mqtt_client.loop_stop()
            # myp.start_mqtt()
            # myp.fatal_error = 0

    myp.save_config()
