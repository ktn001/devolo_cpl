# -*- coding: utf-8 -*-
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: getnetworkoverview.proto
"""Generated protocol buffer code."""
from google.protobuf import descriptor as _descriptor
from google.protobuf import descriptor_pool as _descriptor_pool
from google.protobuf import message as _message
from google.protobuf import reflection as _reflection
from google.protobuf import symbol_database as _symbol_database
# @@protoc_insertion_point(imports)

_sym_db = _symbol_database.Default()




DESCRIPTOR = _descriptor_pool.Default().AddSerializedFile(b'\n\x18getnetworkoverview.proto\x12\nplcnet.api\"\xd5\x06\n\x12GetNetworkOverview\x12>\n\x07network\x18\x01 \x01(\x0b\x32-.plcnet.api.GetNetworkOverview.LogicalNetwork\x1a\x96\x04\n\x06\x44\x65vice\x12\x14\n\x0cproduct_name\x18\x01 \x01(\t\x12\x12\n\nproduct_id\x18\x02 \x01(\t\x12\x18\n\x10\x66riendly_version\x18\x03 \x01(\t\x12\x14\n\x0c\x66ull_version\x18\x04 \x01(\t\x12\x18\n\x10user_device_name\x18\x05 \x01(\t\x12\x19\n\x11user_network_name\x18\x06 \x01(\t\x12\x13\n\x0bmac_address\x18\x07 \x01(\t\x12@\n\x08topology\x18\x08 \x01(\x0e\x32..plcnet.api.GetNetworkOverview.Device.Topology\x12\x44\n\ntechnology\x18\t \x01(\x0e\x32\x30.plcnet.api.GetNetworkOverview.Device.Technology\x12\x17\n\x0f\x62ridged_devices\x18\n \x03(\t\x12\x14\n\x0cipv4_address\x18\x0b \x01(\t\x12\x1a\n\x12\x61ttached_to_router\x18\x0c \x01(\x08\"7\n\x08Topology\x12\x14\n\x10UNKNOWN_TOPOLOGY\x10\x00\x12\t\n\x05LOCAL\x10\x01\x12\n\n\x06REMOTE\x10\x02\"\\\n\nTechnology\x12\x16\n\x12UNKNOWN_TECHNOLOGY\x10\x00\x12\x14\n\x10HPAV_THUNDERBOLT\x10\x03\x12\x10\n\x0cHPAV_PANTHER\x10\x04\x12\x0e\n\nGHN_SPIRIT\x10\x07\x1a^\n\x08\x44\x61taRate\x12\x18\n\x10mac_address_from\x18\x01 \x01(\t\x12\x16\n\x0emac_address_to\x18\x02 \x01(\t\x12\x0f\n\x07tx_rate\x18\x03 \x01(\x01\x12\x0f\n\x07rx_rate\x18\x04 \x01(\x01\x1a\x85\x01\n\x0eLogicalNetwork\x12\x36\n\x07\x64\x65vices\x18\x01 \x03(\x0b\x32%.plcnet.api.GetNetworkOverview.Device\x12;\n\ndata_rates\x18\x02 \x03(\x0b\x32\'.plcnet.api.GetNetworkOverview.DataRateB\r\n\x06plcnetB\x03netb\x06proto3')



_GETNETWORKOVERVIEW = DESCRIPTOR.message_types_by_name['GetNetworkOverview']
_GETNETWORKOVERVIEW_DEVICE = _GETNETWORKOVERVIEW.nested_types_by_name['Device']
_GETNETWORKOVERVIEW_DATARATE = _GETNETWORKOVERVIEW.nested_types_by_name['DataRate']
_GETNETWORKOVERVIEW_LOGICALNETWORK = _GETNETWORKOVERVIEW.nested_types_by_name['LogicalNetwork']
_GETNETWORKOVERVIEW_DEVICE_TOPOLOGY = _GETNETWORKOVERVIEW_DEVICE.enum_types_by_name['Topology']
_GETNETWORKOVERVIEW_DEVICE_TECHNOLOGY = _GETNETWORKOVERVIEW_DEVICE.enum_types_by_name['Technology']
GetNetworkOverview = _reflection.GeneratedProtocolMessageType('GetNetworkOverview', (_message.Message,), {

  'Device' : _reflection.GeneratedProtocolMessageType('Device', (_message.Message,), {
    'DESCRIPTOR' : _GETNETWORKOVERVIEW_DEVICE,
    '__module__' : 'getnetworkoverview_pb2'
    # @@protoc_insertion_point(class_scope:plcnet.api.GetNetworkOverview.Device)
    })
  ,

  'DataRate' : _reflection.GeneratedProtocolMessageType('DataRate', (_message.Message,), {
    'DESCRIPTOR' : _GETNETWORKOVERVIEW_DATARATE,
    '__module__' : 'getnetworkoverview_pb2'
    # @@protoc_insertion_point(class_scope:plcnet.api.GetNetworkOverview.DataRate)
    })
  ,

  'LogicalNetwork' : _reflection.GeneratedProtocolMessageType('LogicalNetwork', (_message.Message,), {
    'DESCRIPTOR' : _GETNETWORKOVERVIEW_LOGICALNETWORK,
    '__module__' : 'getnetworkoverview_pb2'
    # @@protoc_insertion_point(class_scope:plcnet.api.GetNetworkOverview.LogicalNetwork)
    })
  ,
  'DESCRIPTOR' : _GETNETWORKOVERVIEW,
  '__module__' : 'getnetworkoverview_pb2'
  # @@protoc_insertion_point(class_scope:plcnet.api.GetNetworkOverview)
  })
_sym_db.RegisterMessage(GetNetworkOverview)
_sym_db.RegisterMessage(GetNetworkOverview.Device)
_sym_db.RegisterMessage(GetNetworkOverview.DataRate)
_sym_db.RegisterMessage(GetNetworkOverview.LogicalNetwork)

if _descriptor._USE_C_DESCRIPTORS == False:

  DESCRIPTOR._options = None
  DESCRIPTOR._serialized_options = b'\n\006plcnetB\003net'
  _GETNETWORKOVERVIEW._serialized_start=41
  _GETNETWORKOVERVIEW._serialized_end=894
  _GETNETWORKOVERVIEW_DEVICE._serialized_start=128
  _GETNETWORKOVERVIEW_DEVICE._serialized_end=662
  _GETNETWORKOVERVIEW_DEVICE_TOPOLOGY._serialized_start=513
  _GETNETWORKOVERVIEW_DEVICE_TOPOLOGY._serialized_end=568
  _GETNETWORKOVERVIEW_DEVICE_TECHNOLOGY._serialized_start=570
  _GETNETWORKOVERVIEW_DEVICE_TECHNOLOGY._serialized_end=662
  _GETNETWORKOVERVIEW_DATARATE._serialized_start=664
  _GETNETWORKOVERVIEW_DATARATE._serialized_end=758
  _GETNETWORKOVERVIEW_LOGICALNETWORK._serialized_start=761
  _GETNETWORKOVERVIEW_LOGICALNETWORK._serialized_end=894
# @@protoc_insertion_point(module_scope)
