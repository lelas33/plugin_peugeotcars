#!/bin/bash
# version pour Jeedom DIY sur Ubuntu 16.04
PROGRESS_FILE=/tmp/jeedom/peugeotcars/dependance
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
BASEDIR=$(dirname "$0")
echo "BASEDIR: $BASEDIR"

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Installation des dépendances"
echo "============="
echo "STEP1:Upadate"
echo "============="
sudo apt update
echo 10 > ${PROGRESS_FILE}
echo "======================================"
echo "STEP2:Installation python3 version 3.7"
echo "======================================"
sudo add-apt-repository ppa:deadsnakes/ppa
sudo apt update
sudo apt install python3.7
sudo python3 -V
echo 30 > ${PROGRESS_FILE}
echo "==============================================="
echo "STEP3:Installation pip3 version pour python 3.7"
echo "==============================================="
sudo python3 -m pip install pip -U
sudo python3 -m pip -V
echo 40 > ${PROGRESS_FILE}
echo "============================================="
echo "STEP4:Installation des librairies necessaires"
echo "============================================="
PYTHON_REQ=$BASEDIR/../3rdparty/psa_jeedom/requirements.txt
echo "python_req: $PYTHON_REQ"
sudo python3 -m pip install -r $PYTHON_REQ
echo 100 > ${PROGRESS_FILE}
echo "======================================="
echo "Installation des dépendances terminée !"
echo "======================================="
sudo python3 -m site
rm ${PROGRESS_FILE}
