#!/usr/bin/python3

import sys
import argparse
import logging
from jeedom.jeedom import *


def eprint(*args, **kwargs):
    print(*args, file=sys.stderr, **kwargs)

parser = argparse.ArgumentParser()
parser.add_argument('-l', '--loglevel')
args = parser.parse_args()

if args.loglevel:
    jeedom_utils.set_log_level(args.loglevel)
else:
    jeedom_utils.set_log_level()

logging.error("ceci est une erreur")
