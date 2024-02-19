#! /bin/bash
# PiZigate & DIN flash programmation script
# updateFirmware.sh <action> <zigatePort> <zigateType> <lib> [fwfile]
#   where action = flash, check, eraseeeprom
#         zigateType = PI, PIv2, DIN
#         lib = WiringPi, PiGpio
#
# Examples
# ./updateFirmware.sh flash /dev/ttyS0 PI PiGpio ZiGate_v3.23-OPDM.bin
# ./updateFirmware.sh eraseeeprom /dev/ttyS0 PI PiGpio ZiGate_v3.23-OPDM.bin

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)' $@"
echo "---------------------------------"
echo "Démarrage de '$(basename $0)' $@"

# Note: Startup directory is the one from the caller (ajax)
#       It is then '/var/www/html/plugins/Abeille/core/ajax'
PROG_PIv1=${PWD}/../../tmp/JennicModuleProgrammer
BUILD_DIR=${PWD}/../../resources/prog_jennic-0.7/build
PROG_PIv2=${PWD}/../../resources/DK6Programmer
FW_DIR=${PWD}/../../resources/fw_zigate
DIN_SCRIPT=${PWD}/../scripts/flash_ZiGate-DIN.py

# Checks
echo "- Vérifications préliminaires"
echo ${PWD}
error=0
if [ $# -lt 4 ]; then
    echo "= ERREUR: Argument(s) manquant(s) !"
    echo "=         updateFirmware.sh <action> <zigatePort> <zigateType> <GpioLib> [fwfile]"
    exit 1
fi
ACTION=$1
ZGPORT=$2
ZGTYPE=$3
GPIOLIB=$4
if [ ${ZGTYPE} != "PI" ] && [ ${ZGTYPE} != "PIv2" ] && [ ${ZGTYPE} != "DIN" ]; then
    echo "= ERREUR: Type de Zigate invalide ! (PI, PIv2 ou DIN)"
    echo "=         updateFirmware.sh <action> <zigatePort> <zigateType> <GpioLib> [fwfile]"
    exit 1
fi
if [ ${ACTION} == "flash" ] && [ $# -lt 5 ]; then
    echo "= ERREUR: Nom du FW manquant !"
    echo "=         updateFirmware.sh <action> <zigatePort> <zigateType> <GpioLib> [fwfile]"
    exit 1
fi
FW=$5

if [ ${ACTION} != "flash" ] && [ ${ACTION} != "check" ] && [ ${ACTION} != "eraseeeprom" ]; then
    echo "= ERREUR: Action '${ACTION}' non supportée."
    echo "=         Choix=flash/check/eraseeeprom"
    error=1
else
    if [ ${ACTION} == "flash" ]; then
        if [[ "${FW}" == "/"* ]]; then # Absolut path ?
            FW_PATH="${FW}"
        else
            FW_PATH="${FW_DIR}/${FW}"
        fi
        if [ ! -e ${FW_PATH} ]; then
            echo "= ERREUR: le FW choisi n'existe pas !"
            echo "=         FW: ${FW_PATH}"
            error=1
        fi
    fi
    if [ ! -e ${ZGPORT} ]; then
        echo "= ERREUR: le port tty choisi n'existe pas !"
        echo "=         Port: ${ZGPORT}"
        error=1
    fi
    if [ ${GPIOLIB} == "WiringPi" ]; then
        command -v gpio >/dev/null
        if [ $? -ne 0 ]; then
            echo "= ERREUR: Commande 'gpio' manquante ou non exécutable !"
            echo "=         Le package WiringPi est probablement mal installé."
            error=1
        fi
    # elif [ ${GPIOLIB} == "PiGpio" ]; then
    # else
    elif [ ${GPIOLIB} != "PiGpio" ]; then
        echo "= ERREUR: Type de lib GPIO invalid (${GPIOLIB}) !"
        error=1
    fi
fi
if [ $error -ne 0 ]; then
    exit 1
fi
echo "= Ok"

if [ ${ZGTYPE} == "PI" ]; then
    PROG=${PROG_PIv1}

    # (Re)Compiling Jennic programmer v0.7-Abeille
    echo "- Compilation du programmateur"
    pushd ${BUILD_DIR} >/dev/null
    sudo make
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Compilation ratée !"
        error=2
    fi
    # echo "= ERREUR: Programmateur Jennic manquant !"
    # echo "=         ${PROG_PIv1}"
    # error=1
    popd >/dev/null
    if [ ! -x ${PROG_PIv1} ]; then
        # Attempting to correct execution right
        sudo chmod +x ${PROG_PIv1} >/dev/null
    fi
    if [ ! -x ${PROG_PIv1} ]; then
        echo "= ERREUR: Le programmateur Jennic n'est pas exécutable !"
        echo "=         ${PROG_PIv1}"
        error=2
    fi
elif [ ${ZGTYPE} == "PIv2" ]; then
    PROG=${PROG_PIv2}
elif [ ${ZGTYPE} == "DIN" ]; then
    PROG=${DIN_SCRIPT}

    command -v python3 >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: 'python3' manquant ou non exécutable !"
        echo "=         Le package 'python3' est probablement mal installé."
        error=2
    fi
else
    echo "= ERREUR: Type ${ZGTYPE} inconnu !"
    error=2
fi
if [ $error != 0 ]; then
    exit $error
fi

# Si check seulement on quitte ici
if [ ${ACTION} == "check" ]; then
    exit 0
elif [ ${ACTION} == "eraseeeprom" ]; then
    echo "- Effacement de l'EEPROM"
else
    echo "- Lancement de la programmation du firmware"
    echo "  Firmware: ${FW_PATH}"
fi
echo "  Port tty: ${ZGPORT}"
echo "  Type    : ${ZGTYPE}"
echo "  Lib GPIO: ${GPIOLIB}"
echo "  Prog    : ${PROG}"

# Memo connexion PiZiGate
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
    if [ ${GPIOLIB} == "WiringPi" ]; then
        gpio mode 0 out
        gpio mode 2 out

        # Passage en mode 'flash'
        gpio write 2 0
        sleep 1
        gpio write 0 0
        sleep 1
        gpio write 0 1
        sleep 1
    elif [ ${GPIOLIB} == "PiGpio" ]; then
        python /var/www/html/plugins/Abeille/core/scripts/pizigateModeFlash.py
    fi
fi

if [ ${ACTION} == "eraseeeprom" ]; then
    # sudo ${PROG} -V 6 -P 115200 -v --eraseeeprom -s ${ZGPORT} 2>&1
    sudo ${PROG} -V 6 -P 115200 -v --erase -s ${ZGPORT} 2>&1
    if [ $? != 0 ]; then
        echo "= ERREUR: Effacement impossible"
        status=2
    else
        echo "= Ok. Effacement terminé"
        echo "- Redémarrage de la PiZiGate"
        status=0
    fi
else
    if [ ${ZGTYPE} == "DIN" ]; then
        sudo python3 ${DIN_SCRIPT} -s ${ZGPORT} -b 250000 -f ${FW_PATH} 2>&1
    else # PI or PIv2
        sudo ${PROG} -V 6 -P 115200 -v -f ${FW_PATH} -s ${ZGPORT} 2>&1
    fi
    if [ $? != 0 ]; then
        # echo "= ERREUR: Programmation impossible"
        status=2
    else
        echo "= Ok. Programmation faite"
        echo "- Redémarrage de la PiZiGate"
        status=0
    fi
fi

# Switch back to 'prod' mode
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
    if [ ${GPIOLIB} == "WiringPi" ]; then
        gpio write 2 1
        sleep 1
        gpio write 0 0
        sleep 1
        gpio write 0 1
    elif [ ${GPIOLIB} == "PiGpio" ]; then
        python /var/www/html/plugins/Abeille/core/scripts/resetPiZigate.py
    fi
fi

# Final status
if [ $status  -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé. Veuillez vérifier le log ci-dessus."
fi
exit $status
