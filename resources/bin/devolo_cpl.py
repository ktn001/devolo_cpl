#!/usr/bin/python3

import sys
import os
import argparse
import logging
from logging import debug, info, warning, error
import atexit
import json
from jeedom.jeedom import *
import time
import ipaddress
import asyncio

libDir = os.path.realpath(os.path.dirname(__file__) + '/../../3rdparty/devolo_plc_api-1.1.0/')
sys.path.append (libDir)
import devolo_plc_api
import devolo_plc_api.network
from devolo_plc_api import Device

action = ''

#===============================================================================
# eprint
#...............................................................................
# print sur stderr
#===============================================================================
def eprint(*args, **kwargs):
    print(*args, file=sys.stderr, **kwargs)

#===============================================================================
# exit_handler
#...............................................................................
# function appelée automatiquement à la fermeture du programme
#===============================================================================
def exit_handler():
    info ("===================== END =====================")

#===============================================================================
# options
#...............................................................................
# Traitement des options globales de la ligne de commande. Les options
# spécifiques des commande seront traitées dans un deuxième temps
#===============================================================================
def options():
    global action

    parser = argparse.ArgumentParser(allow_abbrev=False)
    actiongrp = parser.add_mutually_exclusive_group(required=True)
    actiongrp.add_argument('--syncDevolo', action='store_true')
    actiongrp.add_argument('--getState', action='store_true')
    parser.add_argument('-l', '--loglevel')
    args = parser.parse_known_args()

    if args[0].loglevel:
        jeedom_utils.set_log_level(args[0].loglevel)
    else:
        jeedom_utils.set_log_level()

    if args[0].syncDevolo:
        action = 'syncDevolo'

    if args[0].getState:
        action = 'getState'

    return args[1]

#===============================================================================
# syncDevolo
#...............................................................................
# Recupère les informations des équipement Devolo pour synchonisation dans
# Jeedom
#===============================================================================
def syncDevolo():
    discovered_devices = devolo_plc_api.network.discover_network()
    result = {}
    devices = {}
    for serial in discovered_devices:
        discovered_devices[serial].connect()
        warning(discovered_devices[serial].__dict__)
        result[serial] = {}
        result[serial]['serial'] = serial
        result[serial]['model'] = discovered_devices[serial].product
        result[serial]['mac'] = discovered_devices[serial].mac
        result[serial]['ip'] = discovered_devices[serial].ip
        network = discovered_devices[serial].plcnet.get_network_overview()
        for i in range (0, len(network.devices)):
            dev = network.devices[i]
            if dev.mac_address in discovered_devices:
                if devices[dev['mac_address']]['name'] != dev['user_device_name']:
                    warning(f"Deux noms différents pour la mac address {dev.mac_address}: '{devices[dev.mac_address]['name']}' et '{dev.user_device_name}'")
            else:
                devices[dev.mac_address] = {}
                devices[dev.mac_address]['name'] = dev.user_device_name
        discovered_devices[serial].disconnect()
    for serial in result:
        result[serial]['name'] = devices[result[serial]['mac']]['name']
    print (json.dumps(result))

#===============================================================================
# getState
#...............................................................................
# Recupère l'état d'un équipement Devolo
#===============================================================================
async def getState(arguments):
    parser = argparse.ArgumentParser(allow_abbrev=False)
    parser.add_argument('--ip', required=True)
    parser.add_argument('-p', action='store_true')
    parser.add_argument('-P' )
    args = parser.parse_args(arguments)

    if args.P:
        password = args.P
    else:
        password = None

    if args.ip:
        try:
            ipaddress.ip_address(args.ip)
        except ValueError:
            error(f"Invalid IP address: {args.ip}")
            sys.exit(2)

    result = {}
    async with Device(ip=args.ip) as dpa:
        if password:
            dpa.password = password
        if await dpa.device.async_get_led_setting():
            result['leds'] = 1
        else:
            result['leds'] = 0

    print (json.dumps(result))
        

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
if not action:
    error("action is not defined")
    sys.exit(1)
info("==================== START ====================")
debug(f"action: {action}")
if action == 'syncDevolo':
    syncDevolo()
elif action == 'getState':
    asyncio.run(getState(args))
else:
    print ("2222222222222222222222222")

