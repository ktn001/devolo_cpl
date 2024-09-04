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

try:
    import jeedom

    print
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

activSerial = dict()


def setActivSerial(serial, ip):
    global activSerial
    now = time.time()
    activSerial[serial] = {"ip": ip, "time": now}
    for s in activSerial.copy():
        if activSerial[s]["time"] < (now - 300):
            activSerial.pop(s)


async def getState(message):
    logging.info("============== begin getState ==============")
    async with Device(ip=message["ip"]) as dpa:
        result = {}
        result["action"] = "infoState"
        result["serial"] = message["serial"]
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

        logging.debug(result)
        setActivSerial(message["serial"], message["ip"])
        jeedom_com.send_change_immediate(result)
    logging.info("=============== end getState ===============")


async def getWifiConnectedDevices(message):
    logging.info("============== begin getWifiConnectedDevices ==============")
    band_txt = ["wifi", "wifi 2 Ghz", "wifi 5 Ghz"]
    try:
        async with Device(ip=message["ip"]) as dpa:
            if "wifi1" in dpa.device.features:
                result = {}
                result["action"] = "wifiConnectedDevices"
                result["serial"] = message["serial"]
                result["connections"] = []
                for (
                    connected_device
                ) in await dpa.device.async_get_wifi_connected_station():
                    connection = {}
                    connection["mac"] = connected_device.mac_address
                    connection["band"] = band_txt[connected_device.band]
                    result["connections"].append(connection)
                logging.debug(result)
                setActivSerial(message["serial"], message["ip"])
                jeedom_com.send_change_immediate(result)
    except (DeviceNotFound, DeviceUnavailable) as e:
        reponse = {}
        reponse["action"] = "message"
        reponse["code"] = "devNotAnswer"
        reponse["serial"] = message["serial"]
        reponse["ip"] = message["ip"]
        jeedom_com.send_change_immediate(reponse)
    logging.info("=============== end getWifiConnectedDevices ===============")


async def getRates(message):
    logging.info("============== begin getRates ==============")
    for ip in message["ip"].split(":"):
        try:
            async with Device(ip) as dpa:
                infos = await dpa.plcnet.async_get_network_overview()
                rates = []
                for i in range(0, len(infos.data_rates)):
                    rate = {}
                    rate["mac_address_from"] = infos.data_rates[i].mac_address_from
                    rate["mac_address_to"] = infos.data_rates[i].mac_address_to
                    rate["tx_rate"] = infos.data_rates[i].tx_rate
                    rate["rx_rate"] = infos.data_rates[i].rx_rate
                    rates.append(rate)
                result = {}
                result["action"] = "getRates"
                result["rates"] = rates
                jeedom_com.send_change_immediate(result)
                firmwares = []
                for i in range(0, len(infos.devices)):
                    firmware = {}
                    firmware["mac"] = infos.devices[i].mac_address
                    firmware["version"] = infos.devices[i].friendly_version
                    firmwares.append(firmware)
                result = {}
                result["action"] = "firmwares"
                result["firmwares"] = firmwares
                setActivSerial(message["serial"], message["ip"])
                jeedom_com.send_change_immediate(result)
                break
        except:
            pass
    logging.info("=============== end getRates ===============")


async def execCmd(message):
    logging.info("============== begin execCmd ==============")
    async with Device(ip=message["ip"]) as dpa:
        if message["password"] != "":
            dpa.password = message["password"]

        ##### leds #####
        if message["cmd"] == "leds":
            logging.info("cmd: 'leds'")
            if message["param"] == 0:
                enable = False
            else:
                enable = True
            success = await dpa.device.async_set_led_setting(enable=enable)
            if success:
                logging.debug("commande 'leds': OK")
            else:
                logging.debug("commande 'leds': KO")

        ##### locate #####
        elif message["cmd"] == "locate":
            logging.info("cmd: 'locate'")
            if message["param"] == 1:
                success = await dpa.plcnet.async_identify_device_start()
                result = {}
                result["action"] = "locate"
                result["serial"] = message["serial"]
                if success:
                    result = {}
                    result["action"] = "infoState"
                    result["serial"] = message["serial"]
                    result["locate"] = 1
                    jeedom_com.send_change_immediate(result)
            else:
                success = await dpa.plcnet.async_identify_device_stop()
                if success:
                    result = {}
                    result["action"] = "infoState"
                    result["serial"] = message["serial"]
                    result["locate"] = 0
                    jeedom_com.send_change_immediate(result)

        ##### guest_on #####
        elif message["cmd"] == "guest_on":
            logging.info("cmd: 'guest_on'")
            if int(message["param"]) > 0:
                logging.debug(f'gest on, duration: {message["param"]}')
                await dpa.device.async_set_wifi_guest_access(
                    enable=True, duration=int(message["param"])
                )
            else:
                logging.debug(f"gest on, duration: indéfini")
                await dpa.device.async_set_wifi_guest_access(enable=True)

        ##### guest_off #####
        elif message["cmd"] == "guest_off":
            await dpa.device.async_set_wifi_guest_access(enable=False)

        ##### actualisation de l'équipement #####
        await getState(message)
    logging.info("=============== end execCmd ===============")


def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    if not JEEDOM_SOCKET_MESSAGE.empty():
        message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode())
        logging.debug("received message: " + str(message))
        if message["apikey"] != _apikey:
            logging.error("Invalid apikey from socket : " + str(message))
            return
        try:
            if message["action"] == "getState":
                asyncio.run(getState(message))
            if message["action"] == "getRates":
                asyncio.run(getRates(message))
            if message["action"] == "getWifiConnectedDevices":
                asyncio.run(getWifiConnectedDevices(message))
            if message["action"] == "execCmd":
                asyncio.run(execCmd(message))
        except (DeviceNotFound, DeviceUnavailable) as e:
            reponse = {}
            reponse["action"] = "message"
            reponse["code"] = "devNotAnswer"
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate(reponse)
        except DevicePasswordProtected as e:
            logging.error("Send command to demon error : " + str(e))
            reponse = {}
            reponse["action"] = "message"
            reponse["code"] = "devPasswordError"
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate(reponse)
        except httpx.HTTPStatusError as e:
            logging.error("Send command to demon error : " + str(e))
            reponse = {}
            reponse["action"] = "message"
            reponse["code"] = "httpxStatusError"
            reponse["message"] = str(e)
            reponse["serial"] = message["serial"]
            reponse["ip"] = message["ip"]
            jeedom_com.send_change_immediate(reponse)
        except Exception as e:
            logging.error(
                "┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            )
            logging.error("┃Send command to demon error : " + str(e))
            logging.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            logging.error("┃" + e.__class__.__name__)
            logging.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            logging.error("┃" + e.__str__())
            logging.error(
                "┠────────────────────────────────────────────────────────────────────"
            )
            logging.error(sys.exc_info()[2])
            logging.error(
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
    logging.debug("========================= SIGALRM ======================")
    signal.alarm(15)
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
_socket_host = "localhost"
_pidfile = "/tmp/demond.pid"
_apikey = ""
_callback = ""
_cycle = 0.3

parser = argparse.ArgumentParser(description="Desmond Daemon for Jeedom plugin")
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

logging.info("┌─Start demond")
logging.info("│ Log level      : " + str(_log_level))
logging.info("│ Socket port    : " + str(_socket_port))
logging.info("│ Socket host    : " + str(_socket_host))
logging.info("│ PID file       : " + str(_pidfile))
logging.info("│ Apikey         : " + str(_apikey))
logging.info("└ Callback       : " + str(_callback))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)
signal.signal(signal.SIGALRM, alrm_handler)
signal.alarm(15)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey=_apikey, url=_callback, cycle=_cycle)
    if not jeedom_com.test():
        logging.error(
            "Network communication issues. Please fixe your Jeedom network configuration."
        )
        shutdown()
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error("Fatal error : " + str(e))
    logging.info(traceback.format_exc())
    shutdown()
