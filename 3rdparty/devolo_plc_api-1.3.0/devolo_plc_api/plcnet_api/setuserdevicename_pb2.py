# -*- coding: utf-8 -*-
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: setuserdevicename.proto
"""Generated protocol buffer code."""
from google.protobuf import descriptor as _descriptor
from google.protobuf import descriptor_pool as _descriptor_pool
from google.protobuf import message as _message
from google.protobuf import reflection as _reflection
from google.protobuf import symbol_database as _symbol_database
# @@protoc_insertion_point(imports)

_sym_db = _symbol_database.Default()




DESCRIPTOR = _descriptor_pool.Default().AddSerializedFile(b'\n\x17setuserdevicename.proto\x12\nplcnet.api\"B\n\x11SetUserDeviceName\x12\x13\n\x0bmac_address\x18\x01 \x01(\t\x12\x18\n\x10user_device_name\x18\x02 \x01(\t\"\xe2\x01\n\x19SetUserDeviceNameResponse\x12<\n\x06result\x18\x01 \x01(\x0e\x32,.plcnet.api.SetUserDeviceNameResponse.Result\"\x86\x01\n\x06Result\x12\x0b\n\x07SUCCESS\x10\x00\x12\x13\n\x0fMACADDR_INVALID\x10\x01\x12\x13\n\x0fMACADDR_UNKNOWN\x10\x02\x12\x17\n\x13\x44\x45VICE_NAME_INVALID\x10\x03\x12\x18\n\x13\x43OMMUNICATION_ERROR\x10\xfe\x01\x12\x12\n\rUNKNOWN_ERROR\x10\xff\x01\x42\x0e\n\x06plcnetB\x04nameb\x06proto3')



_SETUSERDEVICENAME = DESCRIPTOR.message_types_by_name['SetUserDeviceName']
_SETUSERDEVICENAMERESPONSE = DESCRIPTOR.message_types_by_name['SetUserDeviceNameResponse']
_SETUSERDEVICENAMERESPONSE_RESULT = _SETUSERDEVICENAMERESPONSE.enum_types_by_name['Result']
SetUserDeviceName = _reflection.GeneratedProtocolMessageType('SetUserDeviceName', (_message.Message,), {
  'DESCRIPTOR' : _SETUSERDEVICENAME,
  '__module__' : 'setuserdevicename_pb2'
  # @@protoc_insertion_point(class_scope:plcnet.api.SetUserDeviceName)
  })
_sym_db.RegisterMessage(SetUserDeviceName)

SetUserDeviceNameResponse = _reflection.GeneratedProtocolMessageType('SetUserDeviceNameResponse', (_message.Message,), {
  'DESCRIPTOR' : _SETUSERDEVICENAMERESPONSE,
  '__module__' : 'setuserdevicename_pb2'
  # @@protoc_insertion_point(class_scope:plcnet.api.SetUserDeviceNameResponse)
  })
_sym_db.RegisterMessage(SetUserDeviceNameResponse)

if _descriptor._USE_C_DESCRIPTORS == False:

  DESCRIPTOR._options = None
  DESCRIPTOR._serialized_options = b'\n\006plcnetB\004name'
  _SETUSERDEVICENAME._serialized_start=39
  _SETUSERDEVICENAME._serialized_end=105
  _SETUSERDEVICENAMERESPONSE._serialized_start=108
  _SETUSERDEVICENAMERESPONSE._serialized_end=334
  _SETUSERDEVICENAMERESPONSE_RESULT._serialized_start=200
  _SETUSERDEVICENAMERESPONSE_RESULT._serialized_end=334
# @@protoc_insertion_point(module_scope)
