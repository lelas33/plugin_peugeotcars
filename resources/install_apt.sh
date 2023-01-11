#!/bin/bash
# Testé sur les plateformes:

# Raspberry PI V4 : Debian GNU/Linux 10 (buster)
#  Attendu : Python V3.7 déjà installé

PROGRESS_FILE=/tmp/jeedom/peugeotcars/dependance
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
BASEDIR=$(dirname "$0")
echo "BASEDIR: $BASEDIR"
#echo "ADD_PARAM1: $1"  # progress file

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Installation des dépendances"
echo "============"
echo "STEP1:Update"
echo "============"
# sudo apt update
echo 10 > ${PROGRESS_FILE}
sudo python3 -V
echo 30 > ${PROGRESS_FILE}
echo "==============================================="
echo "STEP2:Installation pip3 version pour python 3.7"
echo "==============================================="
sudo python3 -m pip install pip -U
sudo python3 -m pip -V
echo 40 > ${PROGRESS_FILE}
echo "============================================="
echo "STEP3:Installation des librairies necessaires"
echo "============================================="
PYTHON_REQ=$BASEDIR/../3rdparty/psa_jeedom_daemon/requirements.txt
echo "python_req: $PYTHON_REQ"
sudo apt-get -y install python3-typing-extensions python3-pandas python3-plotly python3-paho-mqtt python3-six python3-dateutil python3-brotli libblas-dev  liblapack-dev gfortran python3-pycryptodome libatlas3-base python3-cryptography python3-pip
sudo python3 -m pip install -r $PYTHON_REQ
echo 100 > ${PROGRESS_FILE}
echo "======================================="
echo "Installation des dépendances terminée !"
echo "======================================="
sudo python3 -m site
rm ${PROGRESS_FILE}
