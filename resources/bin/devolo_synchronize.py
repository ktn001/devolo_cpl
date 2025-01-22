
import sys
import os
import argparse
import logging
import atexit
import json
import time
import ipaddress
import asyncio
import traceback
import logging 
import devolo_plc_api
import devolo_plc_api.network
from devolo_plc_api import Device

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + "/../lib")
sys.path.append(libDir)

from jeedom import *

action = ""


# ===============================================================================
# exit_handler
# ...............................................................................
# function appelée automatiquement à la fermeture du programme
# ===============================================================================
def exit_handler():
    logging.info("===================== END =====================")


# ===============================================================================
# options
# ...............................................................................
# Traitement des options globales de la ligne de commande. Les options
# spécifiques des commande seront traitées dans un deuxième temps
# ===============================================================================
def options():
    global action

    parser = argparse.ArgumentParser(allow_abbrev=False)
    parser.add_argument("-l", "--loglevel")
    args = parser.parse_known_args()

    if args[0].loglevel:
        if args[0].loglevel == "fulldebug":
            jeedom_utils.set_log_level("debug")
        jeedom_utils.set_log_level(args[0].loglevel)
    else:
        jeedom_utils.set_log_level()

    return args[1]


# ===============================================================================
# device_definition
# ...............................................................................
# Retourne les infos nécessaires à la création d'un eqLogic
# ===============================================================================
def device_definition(device):
    device.connect()
    print(device)
    print(device.product)
    device.disconnect()


# ===============================================================================
# getDeviceInfos
# ...............................................................................
# Retourne les infos d'un device
# ===============================================================================
async def getDeviceInfos(device, names):
    ret = {}
    await device.async_connect()
    serial = device.serial_number
    try:
        for i in range(20):
            if not hasattr(device.plcnet, "async_get_network_overview"):
                logging.info(
                    f"{serial}: pas d'attribut 'async_get_network_oveview' ==> reconnection {i+1}"
                )
                await device.async_disconnect()
                await device.async_connect()
            else:
                break
        ret["serial"] = serial
        ret["model"] = device.product
        ret["mac"] = device.mac
        ret["ip"] = device.ip
        network = await device.plcnet.async_get_network_overview()
        for i in range(0, len(network.devices)):
            dev = network.devices[i]
            if dev.mac_address in names:
                if names[dev.mac_address] != dev.user_device_name:
                    logging.warning(
                        f"Deux noms différents pour la mac address {dev.mac_address}: {names[dev.mac_address]} et {dev.user_device_name}"
                    )
            else:
                names[dev.mac_address] = dev.user_device_name
    except AttributeError as e:
        logging.warning(f"{serial}: Données incomplètes!")
    await device.async_disconnect()
    return ret


# ===============================================================================
# syncDevolo
# ...............................................................................
# Recupère les informations des équipement Devolo pour synchonisation dans
# Jeedom
# ===============================================================================
async def syncDevolo():
    devices = await devolo_plc_api.network.async_discover_network()
    logging.info(f"{len(devices)} devices found.")
    time.sleep(5)
    result = {}
    names = {}
    tasks = {}
    try:
        for serial in devices:
            tasks[serial] = asyncio.create_task(getDeviceInfos(devices[serial], names))
        for serial in devices:
            result[serial] = await tasks[serial]
        for serial in result:
            if result[serial]["mac"] in names:
                result[serial]["name"] = names[result[serial]["mac"]]
        print(json.dumps(result))

    except Exception as e:
        logging.error("======================================")
        logging.error("Error discovering devices: " + str(e))
        logging.error("--------------------------------------")
        logging.error(e.__class__.__name__)
        logging.error("--------------------------------------")
        logging.error(e)
        logging.error("--------------------------------------")
        logging.error(traceback.format_exc())
        logging.error("======================================")


###########################
#                           #
#  #    #    ##    #  #    #  #
#  ##  ##   #  #   #  ##   #  #
#  # ## #  #    #  #  # #  #  #
#  #    #  ######  #  #  # #  #
#  #    #  #    #  #  #   ##  #
#  #    #  #    #  #  #    #  #
#                           #
###########################

atexit.register(exit_handler)
args = options()
logging.info("==================== START ====================")
asyncio.run(syncDevolo())
