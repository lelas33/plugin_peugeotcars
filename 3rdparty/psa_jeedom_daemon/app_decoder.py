#!/usr/bin/env python3
import json
import os
import sys
import traceback

from androguard.core.bytecodes.apk import APK


import requests
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.serialization import pkcs12
from cryptography.hazmat.backends import default_backend

dossier = os.path.dirname(os.path.abspath(__file__))
dossier = dossier+'/psa_car_controller'
if dossier not in sys.path:
    sys.path.append(dossier)

# from ChargeControl import ChargeControl, ChargeControls
from psa_car_controller.my_psacc import MyPSACC
from sys import argv
import sys
import re

BRAND = {"com.psa.mym.myopel":     {"realm": "clientsB2COpel",     "brand_code": "OP", "app_name": "MyOpel"},
         "com.psa.mym.mypeugeot":  {"realm": "clientsB2CPeugeot",  "brand_code": "AP", "app_name": "MyPeugeot"},
         "com.psa.mym.mycitroen":  {"realm": "clientsB2CCitroen",  "brand_code": "AC", "app_name": "MyCitroen"},
         "com.psa.mym.myds":       {"realm": "clientsB2CDS",       "brand_code": "DS", "app_name": "MyDS"},
         "com.psa.mym.myvauxhall": {"realm": "clientsB2CVauxhall", "brand_code": "VX", "app_name": "MyVauxhall"}
         }

APP_VERSION = "1.33.0"

def getxmlvalue(root, name):
    for child in root.findall("*[@name='" + name + "']"):
        return child.text


def find_app_path():
    base_dir = 'apps/'
    paths = os.listdir(base_dir)
    if len(paths) > 0:
        for path in paths:
            pattern = re.compile('com.psa.mym.\\w*')
            print(pattern.match(path))
            if pattern.match(path) is not None:
                return base_dir + path
    return None


def find_preferences_xml():
    paths = os.listdir()
    if len(paths) > 0:
        for path in paths:
            pattern = re.compile('com.psa.mym.\\w*_preferences.xml')
            if pattern.match(path) is not None:
                return path
    return None


def save_key_to_pem(pfx_data, pfx_password):
    private_key, certificate = pkcs12.load_key_and_certificates(pfx_data,
                                                                pfx_password, default_backend())[:2]
    with open("public.pem", "wb") as f:
        f.write(certificate.public_bytes(encoding=serialization.Encoding.PEM))

    with open("private.pem", "wb") as f:
        f.write(private_key.private_bytes(encoding=serialization.Encoding.PEM,
                                          format=serialization.PrivateFormat.TraditionalOpenSSL,
                                          encryption_algorithm=serialization.NoEncryption()))

current_dir = os.getcwd()
print("current_dir:"+current_dir)
script_dir = dir_path = os.path.dirname(os.path.realpath(__file__))
if sys.version_info < (3, 6):
    raise RuntimeError("This application requres Python 3.6+")

if not argv[1].endswith(".apk"):
    print("No apk given")
    exit(1)
print("APK loading...")

a = APK(argv[1])
package_name = a.get_package()
resources = a.get_android_resources()  # .get_strings_resources()
client_id = resources.get_string(package_name, "PSA_API_CLIENT_ID_PROD")[1]
client_secret = resources.get_string(package_name, "PSA_API_CLIENT_SECRET_PROD")[1]
HOST_BRANDID_PROD = resources.get_string(package_name, "HOST_BRANDID_PROD")[1]
REMOTE_REFRESH_TOKEN = None
print("APK loaded !")

client_email = argv[2]
client_password = argv[3]
country_code = "FR"

## Get Customer id
site_code = BRAND[package_name]["brand_code"] + "_" + country_code + "_ESP"
pfx_cert = a.get_file("assets/MWPMYMA1.pfx")
save_key_to_pem(pfx_cert, b"y5Y2my5B")
try:
    res = requests.post(HOST_BRANDID_PROD + "/GetAccessToken",
                        headers={
                            "Connection": "Keep-Alive",
                            "Content-Type": "application/json",
                            "User-Agent": "okhttp/2.3.0"
                        },
                        params={"jsonRequest": json.dumps(
                            {"siteCode": site_code, "culture": "fr-FR", "action": "authenticate",
                             "fields": {"USR_EMAIL": {"value": client_email},
                                        "USR_PASSWORD": {"value": client_password}}
                            }
                        )}
                        )

    token = res.json()["accessToken"]
except:
    traceback.print_exc()
    print(f"HOST_BRANDID : {HOST_BRANDID_PROD} sitecode: {site_code}")
    print(res.text)
    exit(1)

try:
    res2 = requests.post(
        f"https://mw-{BRAND[package_name]['brand_code'].lower()}-m2c.mym.awsmpsa.com/api/v1/user",
        params={
            "culture": "fr-FR",
            "width": 1080,
            "version": APP_VERSION
        },
        data=json.dumps({"site_code": site_code, "ticket": token}),
        headers={
            "Connection": "Keep-Alive",
            "Content-Type": "application/json;charset=UTF-8",
            "Source-Agent": "App-Android",
            "Token": token,
            "User-Agent": "okhttp/4.8.0",
            "Version": APP_VERSION
        },
        cert=("public.pem", "private.pem"),
    )

    res_dict = res2.json()["success"]
    customer_id = BRAND[package_name]["brand_code"] + "-" + res_dict["id"]

except:
    traceback.print_exc()
    print(res2.text)
    exit(1)

# Psacc
psacc = MyPSACC(None, client_id, client_secret, REMOTE_REFRESH_TOKEN, customer_id, BRAND[package_name]["realm"], country_code)
psacc.connect(client_email, client_password)

os.chdir(current_dir)
psacc.save_config(name="config.json")
res = psacc.get_vehicles()
print(f"\nYour vehicles: {res}")

## Manage OTP and SMS procedure
# request for OPT => SMS request, and remove existing "opt.bin" file
print("Request for OTP: SMS shall be received (on the phone associated to the MyPeugeot account)")
otp = psacc.get_sms_otp_code()
try:
    os.remove("otp.bin")
except:
    print("No previous otp.bin")

try:
    os.remove("private.pem")
    os.remove("public.pem")
except:
    print("Error when deleting temp files")

print("Success !!!")
