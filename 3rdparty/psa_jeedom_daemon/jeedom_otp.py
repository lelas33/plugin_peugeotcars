#!/usr/bin/env python3

# Management of OTP code. (request for SMS and finalize OTP code)
import os
import sys
import argparse
import logging
import socket
import json

path2 = os.path.dirname(os.path.abspath(__file__))
if path2 not in sys.path:
    sys.path.append(path2)

from psa_car_controller.psa.otp.otp import new_otp_session
from psa_car_controller.psacc.application.car_controller import PSACarController
from psa_car_controller.psa.setup.app_decoder import firstLaunchConfig
# from psa_car_controller.common.mylogger import logger


# Constants
BRAND_NAME = {"AP": "com.psa.mym.mypeugeot",
              "AC": "com.psa.mym.mycitroen",
              "DS": "com.psa.mym.myds",
              "VX": "com.psa.mym.myvauxhall",
              "OP": "com.psa.mym.myopel"
              }

PORT = 65433
MSG_SIZE = 64           # Taille message = 64 bytes


logger = logging.getLogger(__name__)
app = PSACarController()

def parse_args_otp():
    parser = argparse.ArgumentParser()
    parser.add_argument("-m", "--mail",     default="",   help="set the email address")
    parser.add_argument("-P", "--password", default="",   help="set the password")
    parser.add_argument("-C", "--country",  default="FR", help="set the country code")
    parser.add_argument("-B", "--brandid",  default="AP", help="set the car brand")
    parser.add_argument("--web-conf",                     help="ignore if config files not existing yet", action='store_true')
    
    parser.parse_args()
    return parser


if __name__ == "__main__":
    # Python version checking
    if sys.version_info < (3, 7):
        raise RuntimeError("This application requires Python 3.7+")
    os.chdir(path2)
    print("Running path:"+path2)

    # get parameters
    parser = parse_args_otp()
    args = parser.parse_args()
    mail = args.mail
    password = args.password
    country = args.country
    pkg_name = BRAND_NAME[args.brandid]
    

    # Step 1: Prepare OTP code generation
    print("Prepare OTP code generation")
    print("Mail:{}, Password:{}, Country:{}, Pkg_name:{} ".format(mail, password, country, pkg_name))
    prepare_ok = 0
    try:
        res = firstLaunchConfig(pkg_name, mail, password, country)
        app.load_app()
        logger.info("OTP_Config:Step1 => Prepare OTP code generation")
        app.start_remote_control()
        logger.info("OTP_Config:Step1 => success")
        prepare_ok = 1
    except Exception as e:
        prepare_ok = 0
        res = str(e)
        logger.exception(e)
        logger.info("OTP_Config:Step1 => Error")

    # Step 1 to 3: Wait for request from operator, through ethernet messages
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('localhost', PORT))
        s.listen()
        while True:
            (conn, addr) = s.accept()
            with conn:
                # message reception
                # logger.info('OTP_Config:Connected by %s', addr)
                # logger.info('OTP_Config:Get message')
                cmd_msg = conn.recv(MSG_SIZE)
                logger.info('OTP_Config:message received: %s', cmd_msg)
                
                # message and its params decoding
                cmd_cmd = 0
                if (cmd_msg[0] == 0xff) and (cmd_msg[1] == 0xf0) and (cmd_msg[2] == 0x00):
                    cmd_cmd = cmd_msg[3]   # commande valide
                    # additional parameters (json format)
                    param_length = cmd_msg[4]   # nb bytes of parameters
                    if (param_length > MSG_SIZE - 5):
                        param_length = MSG_SIZE - 5
                    logger.debug('OTP_Config:message cmd_cmd: %d', cmd_cmd)
                    # logger.debug('OTP_Config:message param_length: %d', param_length)
                    if (param_length != 0):
                        param_data = ""           # parameters payload
                        for idx in range(0, param_length):
                            param_data = param_data + chr(cmd_msg[5+idx])
                        logger.debug('OTP_Config:param_data: %s', param_data)
                        cmd_params = json.loads(param_data)
                    else:
                        cmd_params = []
                logger.info('OTP_Config:message params: %s', cmd_params)

                # commands processing
                ack_val = 0
                if (cmd_cmd == 1):
                    # Sync for preparation of OTP code generation
                    logger.info('OTP_Config:Step1 =>Sync for preparation of OTP code generation')
                    ack_val = prepare_ok

                elif (cmd_cmd == 2):
                    # Request SMS to Stellantis
                    logger.info("OTP_Config:Step2 => Request SMS to Stellantis")
                    # Request SMS
                    try:
                        app.myp.remote_client.get_sms_otp_code()
                        logger.info("OTP_Config:Step2 => SMS sent")
                        ack_val = 1
                    except Exception as e:
                        ack_val = 0
                        res = str(e)
                        logger.error("OTP_Config:Step2 => SMS request not performed:%s", res)

                elif (cmd_cmd == 3):
                    # Finalize OTP code generation
                    logger.info("OTP_Config:Step3 => Finalize OTP code generation")
                    code_sms = cmd_params['code_sms']
                    code_pin = cmd_params['code_pin']
                    # logger.info('OTP_Config:code_sms: %s', code_sms)
                    # logger.info('OTP_Config:code_pin: %s', code_pin)

                    try:
                        otp_session = new_otp_session(code_sms, code_pin, app.myp.remote_client.otp)
                        app.myp.remote_client.otp = otp_session
                        app.myp.save_config()
                        app.start_remote_control()
                        logger.info("OTP_Config:Step3 => OTP config correctly finished !!!")
                        ack_val = 1
                    except Exception as e:
                        ack_val = 0
                        res = str(e)
                        logger.error("OTP_Config:Step3 => OTP config not finished correctly:%s", res)
                
                # Acknowledge return
                ack_msg = [0x00]*MSG_SIZE  # buffer emission message retour
                ack_msg[0] = 0xff
                ack_msg[1] = 0x0f
                ack_msg[2] = 0x00
                ack_msg[3] = ack_val
                conn.sendall(bytes(ack_msg))

