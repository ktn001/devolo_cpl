# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import traceback
import re
import signal
from optparse import OptionParser
from os.path import join
import json
import argparse
import asyncio
import httpx

libDir = os.path.realpath(os.path.dirname(__file__) + '/../../3rdparty/devolo_plc_api-1.1.0/')
sys.path.append (libDir)
import devolo_plc_api
from devolo_plc_api import Device
from devolo_plc_api.exceptions.device import *

try:
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

async def getState (message):
    logging.info("============== getState ==============")
    async with Device(ip=message['ip']) as dpa:
        result = {}
        result['action'] = 'infoState'
        result['serial'] = message['serial']
        if message['password'] != '':
            dpa.password = message['password']
        if await dpa.device.async_get_led_setting():
            result['leds'] = 1
        else:
            result['leds'] = 0
        logging.debug(result)
        jeedom_com.send_change_immediate(result)

async def execCmd (message):
    logging.info("============== execCmd ==============")
    async with Device(ip=message['ip']) as dpa:
        if message['password'] != '':
            dpa.password = message['password']
        
        ##### leds #####
        if message['cmd'] == 'leds':
            logging.info("cmd: 'leds'")
            if message['param'] == 0:
                enable=False
            else:
                enable=True
            success = await dpa.device.async_set_led_setting(enable=enable)
            if success:
                logging.debug("commande 'leds': OK")
            else:
                logging.debug("commande 'leds': KO")

        ##### locate #####
        if message['cmd'] == 'locate':
            logging.info("cmd: 'locate'")
            if message['param'] == 1:
                success = await dpa.plcnet.async_identify_device_start()
                result = {}
                result['action'] = 'locate'
                result['serial'] = message['serial']
                if success:
                    result = {}
                    result['action'] = 'infoState'
                    result['serial'] = message['serial']
                    result['locate'] = 1
                    jeedom_com.send_change_immediate(result)
            else:
                success = await dpa.plcnet.async_identify_device_stop()
                if success:
                    result = {}
                    result['action'] = 'infoState'
                    result['serial'] = message['serial']
                    result['locate'] = 0
                    jeedom_com.send_change_immediate(result)

        ##### actualisation de l'équipement #####
        await getState(message)


def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    if not JEEDOM_SOCKET_MESSAGE.empty():
        message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode())
        logging.debug("received massage: " + str(message))
        if message['apikey'] != _apikey:
            logging.error("Invalid apikey from socket : " + str(message))
            return
        try:
            if message['action'] == 'getState':
                asyncio.run(getState(message))
            if message['action'] == 'execCmd':
                asyncio.run(execCmd(message))
        except DeviceNotFound as e:
            logging.error('Send command to demon error : '+str(e))
            reponse = {}
            reponse['action'] = 'message'
            reponse['code'] = 'devNotAnswer'
            reponse['serial'] = message['serial']
            reponse['ip'] = message['ip']
            jeedom_com.send_change_immediate(reponse)
        except DevicePasswordProtected as e:
            logging.error('Send command to demon error : '+str(e))
            reponse = {}
            reponse['action'] = 'message'
            reponse['code'] = 'devPasswordError'
            reponse['serial'] = message['serial']
            reponse['ip'] = message['ip']
            jeedom_com.send_change_immediate(reponse)
        except httpx.HTTPStatusError as e:
            logging.error('Send command to demon error : '+str(e))
            reponse = {}
            reponse['action'] = 'message'
            reponse['code'] = 'httpxStatusError'
            reponse['message'] = str(e)
            reponse['serial'] = message['serial']
            reponse['ip'] = message['ip']
            jeedom_com.send_change_immediate(reponse)
        except Exception as e:
            logging.error("+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++")
            logging.error('Send command to demon error : '+str(e))
            logging.error("---------------------------------------------------------------------")
            logging.error(e.__class__.__name__)
            logging.error("---------------------------------------------------------------------")
            logging.error(e)
            logging.error("+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++")

def listen():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()

def shutdown():
    logging.debug("Shutdown")
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    try:
        jeedom_socket.close()
    except:
        pass
    try:
        jeedom_serial.close()
    except:
        pass
    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 0
_socket_host = 'localhost'
_pidfile = '/tmp/demond.pid'
_apikey = ''
_callback = ''
_cycle = 0.3

parser = argparse.ArgumentParser(
    description='Desmond Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Port for message from Jeedom", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.cycle:
    _cycle = float(args.cycle)
if args.socketport:
    _socket_port = args.socketport
        
_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))
logging.info('Callback : '+str(_callback))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)    

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
    if not jeedom_com.test():
        logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
        shutdown()
    jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : '+str(e))
    logging.info(traceback.format_exc())
    shutdown()

