#!/usr/bin/env python3
import atexit
import sys
import os
import time
from oauth2_client.credentials_manager import OAuthError
from threading import Thread

import argparse

from MyLogger import my_logger
from MyLogger import logger
from MyPSACC import MyPSACC

import jeedom_server

parser = argparse.ArgumentParser()


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument("-d", "--debug", help="enable debug", const=10, default=20, nargs='?', metavar='Debug level number')
    parser.add_argument("-m", "--mail", help="set the email address")
    parser.add_argument("-P", "--password", help="set the password")
    parser.add_argument("-s", "--smscode", help="set sms code for OTP")
    parser.add_argument("-p", "--pincode", help="set appli pin code")
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
    myp = MyPSACC.load_config()
    myp.set_codes(args.smscode, args.pincode)
    atexit.register(myp.save_config)
    try:
        myp.manager._refresh_token()
    except OAuthError:
        if args.mail and args.password:
            client_email = args.mail
            client_password = args.password
            myp.connect(client_email, client_password)
        else:
            logger.error("Mail and Password undefined")
    logger.info(myp.get_vehicles())
    vin = myp.getVIN()
    logger.info("VIN:"+vin[0])
    logger.info("MQTT link start")
    myp.start_mqtt()

    # Start communication with Jeedom plugin using TCP socket (@localhost)
    logger.info("Jeedom link start")
    js = jeedom_server.my_jeedom_server(myp, vin[0])
    # run the main loop for jeedom link
    t1 = Thread(target=js.server_loop)
    t1.start()

    # Monitoring for MQTT server: (1 check every 0.5 minute)
    #  * "fatal error" of MQTT server, in order to restart it if necessary 
    #  * Message not processed due to token expired
    while True:
        time.sleep(30)
        if myp.fatal_error == 1:
            logger.info("Fatal error detected => Restart MQTT link")
            myp.mqtt_client.loop_stop()
            myp.start_mqtt()
            myp.fatal_error = 0
        if myp.resend_command == 1:
            logger.info("Token expired error detected => Resend last command")
            js.msg_resend_last_cmd()
            myp.resend_command = 0
        

    myp.save_config()
