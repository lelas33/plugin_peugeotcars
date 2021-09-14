#!/bin/bash
# Testé sur les plateformes:

# Jeedom DIY     : Ubuntu 16.04
#  Installation Python V3.7 : Decommenter les lignes 32-34
#  Installation PIP 21.0    : Decommenter la ligne 40

# Raspberry PI V3 : Debian GNU/Linux 10 (buster)
#  Attendu : Python V3.7 et pip 21.0 déjà installé

PROGRESS_FILE=/tmp/jeedom/peugeotcars/dependance
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
BASEDIR=$(dirname "$0")
echo "BASEDIR: $BASEDIR"
#echo "ADD_PARAM1: $1"  # progress file
#echo "ADD_PARAM2: $2"  # email
#echo "ADD_PARAM3: $3"  # password
#echo "ADD_PARAM4: $4"  # brand code

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Installation des dépendances"
echo "============"
echo "STEP1:Update"
echo "============"
# sudo apt update
echo 10 > ${PROGRESS_FILE}
echo "======================================"
echo "STEP2:Installation python3 version 3.7"
echo "======================================"
# sudo add-apt-repository ppa:deadsnakes/ppa
# sudo apt update
# sudo apt install python3.7
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
PYTHON_REQ=$BASEDIR/../3rdparty/psa_jeedom_daemon/requirements.txt
echo "python_req: $PYTHON_REQ"
sudo python3 -m pip install -r $PYTHON_REQ
echo 80 > ${PROGRESS_FILE}
echo "========================="
echo "STEP5:Configuration API  "
echo "========================="
PSA_JEEDOM_DIR=$BASEDIR/../3rdparty/psa_jeedom_daemon
cd $PSA_JEEDOM_DIR
if [ "$4" = "AP" ]
then
    echo "Appli Peugeot"
    wget -nv https://github.com/lelas33/plugin_db/raw/master/psa_apk/myp
    tar xvzf ./myp > /dev/null
    MY_APP=./myp.apk
    MY=./myp
elif [ "$4" = "AC" ]
then
    echo "Appli Citroën"
    wget -nv https://github.com/lelas33/plugin_db/raw/master/psa_apk/myc
    tar xvzf ./myc > /dev/null
    MY_APP=./myc.apk
    MY=./myc
elif [ "$4" = "DS" ]
then
    echo "Appli Citroën-DS"
    wget -nv https://github.com/lelas33/plugin_db/raw/master/psa_apk/myd
    tar xvzf ./myd > /dev/null
    MY_APP=./myd.apk
    MY=./myd
elif [ "$4" = "OP" ]
then
    echo "Appli Opel"
    wget -nv https://github.com/lelas33/plugin_db/raw/master/psa_apk/myo
    tar xvzf ./myo > /dev/null
    MY_APP=./myo.apk
    MY=./myo
elif [ "$4" = "VX" ]
then
    echo "Appli Vauxhall"
    wget -nv https://github.com/lelas33/plugin_db/raw/master/psa_apk/myv
    tar xvzf ./myv > /dev/null
    MY_APP=./myv.apk
    MY=./myv
else
    echo "Erreur sur le parametre BRAND"
fi
APP_DECODER=./app_decoder.py
sudo python3 $APP_DECODER $MY_APP $2 $3
rm $MY_APP $MY

echo 100 > ${PROGRESS_FILE}
echo "======================================="
echo "Installation des dépendances terminée !"
echo "======================================="
sudo python3 -m site
rm ${PROGRESS_FILE}
