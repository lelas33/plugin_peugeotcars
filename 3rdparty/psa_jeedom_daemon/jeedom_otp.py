#!/usr/bin/env python3

# Management of OTP code. (request for SMS and finalize OTP code)
import os
import sys
import argparse
import logging

path2 = os.path.dirname(os.path.abspath(__file__))
if path2 not in sys.path:
    sys.path.append(dossier)

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

logger = logging.getLogger(__name__)
app = PSACarController()

def parse_args_otp():
    parser = argparse.ArgumentParser()
    # parser.add_argument("-r", "--req",      default=0,  help="1:prepare, 2:request SMS, 3:finalize OTP")
    parser.add_argument("-m", "--mail",     default="", help="set the email address")
    parser.add_argument("-P", "--password", default="", help="set the password")
    parser.add_argument("-c", "--country",  default="FR", help="set the country code")
    parser.add_argument("-b", "--brandid",  default="AP", help="set car brand")
    
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
    # request = int(args.req)

    while True:
        request = int(input('Requete => 1:prepare, 2:request SMS, 3:finalize OTP ... '))
        
        # Manage SMS request to Stellantis
        if request == 1:
            print("Prepare OTP code generation")
            print("Mail:{}, Password:{}, Country:{}, Pkg_name:{} ".format(mail, password, country, pkg_name))
            logger.info("Prepare OTP code generation")
            # Start OTP Code preparation
            try:
                res = firstLaunchConfig(pkg_name, mail, password, country)
                app.load_app()
                app.start_remote_control()
                print(" => success")
            except Exception as e:
                res = str(e)
                logger.exception(e)
                print(" => Error")
            
            
        # Manage SMS request to Stellantis
        elif request == 2:
            print("Request SMS to Stellantis")
            logger.info("Request SMS to Stellantis")
            # Request SMS
            try:
                app.myp.remote_client.get_sms_otp_code()
                print(" => SMS sent")
            except Exception as e:
                res = str(e)
                print(" => Error:", res)
            
        # Finalize OTP code generation
        elif request == 3:
            print("Finalize OTP code generation")
            logger.info("Finalize OTP code generation")
            try:
                otp_session = new_otp_session(sms_code, code_pin, app.myp.remote_client.otp)
                app.myp.remote_client.otp = otp_session
                app.myp.save_config()
                app.start_remote_control()
                print(" => OTP config finished !!! ")
            except Exception as e:
                res = str(e)
                logger.exception("finishOtp:")
                print(" => Error:", res)

        
        # Finalize OTP code generation
        elif request == 4:
            print("Test 4:OK")

        # Error of req parameter
        else:
            print("Request parameter error")
            exit()
    


