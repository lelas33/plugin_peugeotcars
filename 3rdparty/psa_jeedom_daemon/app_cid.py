#!/usr/bin/env python3
import json
import os
import sys
import traceback
import base64

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
         "com.psa.mym.myds":       {"realm": "clientsB2CDS",       "brand_code": "AC", "app_name": "MyDS"},
         "com.psa.mym.myvauxhall": {"realm": "clientsB2CVauxhall", "brand_code": "0V", "app_name": "MyVauxhall"}
         }


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
    private_key, certificate, additional_certificates = pkcs12.load_key_and_certificates(pfx_data,
                                                                                         bytes.fromhex(pfx_password),
                                                                                         default_backend())
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
# pfx_cert = a.get_file("assets/MWPMYMA1.pfx")
# remote_refresh_token = None
print("APK loaded !")

message_bytes = client_id.encode('ascii')
base64_bytes  = base64.b64encode(message_bytes)
client_id_b64 = base64_bytes.decode('ascii')

message_bytes = client_secret.encode('ascii')
base64_bytes  = base64.b64encode(message_bytes)
client_secret_b64 = base64_bytes.decode('ascii')


print("client_id:        ", client_id)
print("client_id_b64:    ", client_id_b64)
print("client_secret:    ", client_secret)
print("client_secret_b64:", client_secret_b64)
print("HOST_BRANDID_PROD:", HOST_BRANDID_PROD)

