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
#

#import time
import logging
#import threading
#import requests
#import datetime
#try:
#    from collections.abc import Mapping
#except ImportError:
#    from collections import Mapping
#import os
#from os.path import join
#import socket
#from queue import Queue
#import socketserver
#from socketserver import (TCPServer, StreamRequestHandler)
#import signal
#import unicodedata

# ------------------------------------------------------------------------------

class jeedom_utils():

	@staticmethod
	def convert_log_level(level = 'error'):
		LEVELS = {'debug': logging.DEBUG,
		  'info': logging.INFO,
		  'notice': logging.WARNING,
		  'warning': logging.WARNING,
		  'error': logging.ERROR,
		  'critical': logging.CRITICAL,
		  'none': logging.CRITICAL}
		return LEVELS.get(level, logging.CRITICAL)

	@staticmethod
	def set_log_level(level = 'error'):
		FORMAT = '[%(asctime)-15s][%(levelname)s][%(filename)s] : %(message)s'
		logging.basicConfig(level=jeedom_utils.convert_log_level(level),format=FORMAT, datefmt="%Y-%m-%d %H:%M:%S")

	@staticmethod
	def stripped(str):
		return "".join([i for i in str if i in range(32, 127)])

	@staticmethod
	def ByteToHex( byteStr ):
		return byteStr.hex()

	@staticmethod
	def dec2bin(x, width=8):
		return ''.join(str((x>>i)&1) for i in xrange(width-1,-1,-1))

	@staticmethod
	def dec2hex(dec):
		if dec is None:
			return '0x00'
		return "0x{:02X}".format(dec)

	@staticmethod
	def testBit(int_type, offset):
		mask = 1 << offset
		return(int_type & mask)

	@staticmethod
	def clearBit(int_type, offset):
		mask = ~(1 << offset)
		return(int_type & mask)

	@staticmethod
	def split_len(seq, length):
		return [seq[i:i+length] for i in range(0, len(seq), length)]

	@staticmethod
	def write_pid(path):
		pid = str(os.getpid())
		logging.info("Writing PID " + pid + " to " + str(path))
		open(path, 'w').write("%s\n" % pid)

	@staticmethod
	def remove_accents(input_str):
		nkfd_form = unicodedata.normalize('NFKD', unicode(input_str))
		return u"".join([c for c in nkfd_form if not unicodedata.combining(c)])

	@staticmethod
	def printHex(hex):
		return ' '.join([hex[i:i + 2] for i in range(0, len(hex), 2)])

# ------------------------------------------------------------------------------
# END
# ------------------------------------------------------------------------------
