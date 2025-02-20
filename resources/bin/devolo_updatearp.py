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

import os
import sys
import argparse
import subprocess
import traceback
import re
import time
import ipaddress
import queue
import threading

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + "/../lib")
sys.path.append(libDir)
from jeedom import *

_apikey = ''
_callback = ''

NB_THREADS = 20
queue = queue.Queue()

def pingIps():
    while True:
        ip = queue.get()
        if ip == 'stop':
            return
        process = subprocess.run(["/usr/bin/ping", "-c", "1", "-W", "0.1", "-n", ip], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

_log_level = "error"
parser = argparse.ArgumentParser (description="ping each address on subnets")
parser.add_argument ("--loglevel", "-l", help="Log Level for the daemon", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--callback", help="Callback", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.apikey:
    _apikey = args.apikey
if args.callback:
    _callback = args.callback

jeedom_utils.set_log_level(_log_level)
logger = logging.getLogger('devolo_updatearp')

logger.info("Start")

try:
    process = subprocess.run (['/usr/bin/ip', 'add'], capture_output=True )
    process.check_returncode()
except Exception as e:
    logger.error (e)
    logger.error (process.stderr.decode().replace("\n",""))
    lines = traceback.format_exc().splitlines()
    for line in lines:
        logger.debug(line)
    sys.exit(1)

broadcasts = dict()

for line in process.stdout.decode().splitlines():
    tokens = re.split("\s+", line)
    if tokens[1] != 'inet' or tokens[3] != 'brd':
        continue
    broadcasts[tokens[4]] = tokens[2]

for i in range (0, NB_THREADS):
    thread = threading.Thread(target=pingIps)
    thread.start()

for broadcast in broadcasts:
    network = ipaddress.IPv4Network (broadcasts[broadcast], False)
    for ip in network:
        queue.put(str(ip))

for i in range (0, NB_THREADS):
    queue.put('stop')

while (threading.active_count() > 1):
    time.sleep(2)

time.sleep(10)

jeedom_com = jeedom_com(apikey=_apikey, url=_callback, cycle=0)
jeedom_com.add_changes('arpUpdated','ok')
logger.info("End")
