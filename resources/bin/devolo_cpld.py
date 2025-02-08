# vim: tabstop=4 autoindent expandtab
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
import devolo_plc_api
from devolo_plc_api import Device
from devolo_plc_api.exceptions.device import *

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + "/../lib")
sys.path.append(libDir)

from jeedom import *

activSerial = dict()

def setActivSerial(serial, ip):
    global activSerial
    now = time.time()
    activSerial[serial] = {"ip": ip, "time": now}
    for s in activSerial.copy():
        if activSerial[s]["time"] < (now - 300):
            activSerial.pop(s)


async def getState(message):
    logger.info("============== begin getState ==============")
    async with Device(ip=message["ip"]) as dpa:
        result = {}
        if message["password"] != "":
            dpa.password = message["password"]

        # leds
        if await dpa.device.async_get_led_setting():
            result["leds"] = 1
        else:
            result["leds"] = 0

        # firmware
        firmware = await dpa.device.async_check_firmware_available()
        if firmware.result == devolo_plc_api.device_api.UPDATE_AVAILABLE:
            result["firmwareAvailable"] = 1
        else:
            result["firmwareAvailable"] = 0
        result["nextFirmware"] = firmware.new_firmware_version

        # wifi guest
        if "wifi1" in dpa.device.features:
            guest_wifi = await dpa.device.async_get_wifi_guest_access()
            if guest_wifi:
                result["wifi_guest"] = {}
                if guest_wifi.enabled:
                    result["wifi_guest"]["enabled"] = 1
                else:
                    result["wifi_guest"]["enabled"] = 0
                result["wifi_guest"]["remaining"] = guest_wifi.remaining_duration

        key = "infoState::" + message['serial']
        logger.info(key + str(result))
        setActivSerial(message["serial"], message["ip"])
        jeedom_com.add_changes(key, json.dumps(result))
    logger.info("=============== end getState ===============")


async def getRates(message):
    logger.info("============== begin getRates ==============")
    rates = {}
    for ip in message["ip"].split(":"):
        try:
            async with Device(ip) as dpa:
                network = await dpa.plcnet.async_get_network_overview()
                for rate in network.data_rates:
                    mac_from = rate.mac_address_from
                    mac_to = rate.mac_address_to
                    if mac_from not in rates:
                        rates[mac_from] = {}
                    if mac_to not in rates[mac_from]:
                        rates[mac_from][mac_to] = {}
                    rates[mac_from][mac_to]['tx_rate'] = rate.tx_rate
                    rates[mac_from][mac_to]['rx_rate'] = rate.rx_rate
        except:
            pass
    for src in rates.keys():
        for dst in rates[src].keys():
            key = f'rates::{src}::{dst}'
            jeedom_com.add_changes(key, json.dumps(rates[src][dst]))

    logger.info("=============== end getRates ===============")


async def getWifiConnectedDevices(message):
    logger.info("============== begin getWifiConnectedDevices ==============")
    logger.info(message)
    band_txt = ["wifi", "wifi 2 Ghz", "wifi 5 Ghz"]
    try:
        async with Device(ip=message["ip"]) as dpa:
            if "wifi1" in dpa.device.features:
                result = {}
                connections = []
                for (
                    connected_device
                ) in await dpa.device.async_get_wifi_connected_station():
                    connection = {}
                    connection["mac"] = connected_device.mac_address
                    connection["band"] = band_txt[connected_device.band]
                    connections.append(connection)
                setActivSerial(message["serial"], message["ip"])
                key = f'wifiConnectedDevices::{message["serial"]}'
                jeedom_com.add_changes(key, json.dumps(connections))
    except (DeviceNotFound, DeviceUnavailable) as e:
        reponse = {}
        reponse["code"] = "devNotAnswer"
        reponse["serial"] = message["serial"]
        reponse["ip"] = message["ip"]
        jeedom_com.send_change_immediate({'message': reponse})
    logger.info("=============== end getWifiConnectedDevices ===============")


async def execCmd(message):
    logger.info("============== begin execCmd ==============")
    async with Device(ip=message["ip"]) as dpa:
        if message["password"] != "":
            dpa.password = message["password"]

        ##### leds #####
        if message["cmd"] == "leds":
            logger.info("cmd: 'leds'")
            if message["param"] == 0:
                enable = False
            else:
                enable = True
            success = await dpa.device.async_set_led_setting(enable=enable)
            if success:
                logger.debug("commande 'leds': OK")
            else:
                logger.debug("commande 'leds': KO")

        ##### locate #####
        elif message["cmd"] == "locate":
            logger.info("cmd: 'locate'")
            if message["param"] == 1:
                success = await dpa.plcnet.async_identify_device_start()
                if success:
                    result = {}
                    result["locate"] = 1
                    jeedom_com.send_change_immediate({'infoState': { message['serial']: result}})
            else:
                success = await dpa.plcnet.async_identify_device_stop()
                if success:
                    result = {}
                    result["locate"] = 0
                    jeedom_com.send_change_immediate({'infoState': { message['serial']: result}})

        ##### guest_on #####
        elif message["cmd"] == "guest_on":
            logger.info("cmd: 'guest_on'")
            if int(message["param"]) > 0:
                logger.debug(f'gest on, duration: {message["param"]}')
                await dpa.device.async_set_wifi_guest_access(
                    enable=True, duration=int(message["param"])
                )
            else:
                logger.debug(f"gest on, duration: indéfini")
                await dpa.device.async_set_wifi_guest_access(enable=True)

        ##### guest_off #####
        elif message["cmd"] == "guest_off":
            await dpa.device.async_set_wifi_guest_access(enable=False)

        ##### actualisation de l'équipement #####
        await getState(message)
    logger.info("=============== end execCmd ===============")


def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    if not JEEDOM_SOCKET_MESSAGE.empty():
        message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode())
        logger.debug("received message: " + str(message))
        if message["apikey"] != _apikey:
            logger.error("Invalid apikey from socket : " + str(message))
            return
        try:
            if message["action"] == "getState":
                asyncio.run(getState(message))
            if message["action"] == "getRates":
                asyncio.run(getRates(message))
            if message["action"] == "execCmd":
                asyncio.run(execCmd(message))
        except (DeviceNotFound, DeviceUnavailable) as e:
            reponse = {}
            reponse["code"] = "devNotAnswer"
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate({'message': reponse})
        except DevicePasswordProtected as e:
            logger.error("Send command to demon error : " + str(e))
            reponse = {}
            reponse["code"] = "devPasswordError"
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate({'message': reponse})
        except httpx.HTTPStatusError as e:
            logger.error("Send command to demon error : " + str(e))
            reponse = {}
            reponse["code"] = "httpxStatusError"
            reponse["message"] = str(e)
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate({'message': reponse})
        except Exception as e:
            logger.error(
                "┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            )
            logger.error("┃Send command to demon error : " + str(e))
            logger.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            logger.error("┃" + e.__class__.__name__)
            logger.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            logger.error("┃" + e.__str__())
            logger.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            for line in traceback.format_exc().splitlines():
                logger.error("┃" + line)
            logger.error(
                "┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            )


def listen():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()


# ----------------------------------------------------------------------------


def alrm_handler(signum=None, frame=None):
    logger.debug("========================= SIGALRM ======================")
    signal.alarm(15) # 15 secondes
    for serial in activSerial.copy():
        message = {"serial": serial, "ip": activSerial[serial]["ip"]}
        try:
            loop = asyncio.get_running_loop()
        except:
            loop = None
        if loop and loop.is_running():
            loop.create_task(getWifiConnectedDevices(message))
        else:
            asyncio.run(getWifiConnectedDevices(message))


def handler(signum=None, frame=None):
    logger.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logger.debug("Shutdown")
    logger.debug("Removing PID file " + str(_pidfile))
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
    logger.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)


# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 0
_socket_host = "localhost"
_pidfile = "/tmp/demond.pid"
_apikey = ""
_callback = ""
_cycle = 0.5
_discret_log = False

parser = argparse.ArgumentParser(description="Desmond Daemon for Jeedom plugin")
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Port for message from Jeedom", type=str)
parser.add_argument("--discretLogs", help="hide passwords in log", action='store_true')
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
if args.discretLogs:
    _discret_log = True

_socket_port = int(_socket_port)

if _log_level == 'debug':
    jeedom_utils.set_log_level(_log_level)
    for module in ['urllib3', 'asyncio', 'httpx', 'httpcore', 'devolo_plc_api'] :
        logging.getLogger(module).setLevel(logging.WARNING)
elif _log_level == 'fulldebug':
    jeedom_utils.set_log_level('debug')
else:    
    jeedom_utils.set_log_level(_log_level)
logger = logging.getLogger('devolo_cpld')

logger.info("┌─Start demond")
logger.info("│ Log level      : " + str(_log_level))
logger.info("│ Socket port    : " + str(_socket_port))
logger.info("│ Socket host    : " + str(_socket_host))
logger.info("│ PID file       : " + str(_pidfile))
logger.info("│ Apikey         : " + str(_apikey))
logger.info("└ Callback       : " + str(_callback))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)
signal.signal(signal.SIGALRM, alrm_handler)
signal.alarm(15)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey=_apikey, url=_callback, cycle=_cycle)
    if not jeedom_com.test():
        logger.error(
            "Network communication issues. Please fixe your Jeedom network configuration."
        )
        shutdown()
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host, discret=_discret_log)
    listen()
except Exception as e:
    logger.error("Fatal error : " + str(e))
    logger.info(traceback.format_exc())
    shutdown()
