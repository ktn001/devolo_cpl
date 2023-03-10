# -*- coding: utf-8 -*-
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: updatefirmware.proto
"""Generated protocol buffer code."""
from google.protobuf import descriptor as _descriptor
from google.protobuf import descriptor_pool as _descriptor_pool
from google.protobuf import message as _message
from google.protobuf import reflection as _reflection
from google.protobuf import symbol_database as _symbol_database
# @@protoc_insertion_point(imports)

_sym_db = _symbol_database.Default()




DESCRIPTOR = _descriptor_pool.Default().AddSerializedFile(b'\n\x14updatefirmware.proto\x12\ndevice.api\"\xb9\x01\n\x13UpdateFirmwareCheck\x12\x36\n\x06result\x18\x01 \x01(\x0e\x32&.device.api.UpdateFirmwareCheck.Result\x12\x1c\n\x14new_firmware_version\x18\x02 \x01(\t\"L\n\x06Result\x12\x14\n\x10UPDATE_AVAILABLE\x10\x00\x12\x18\n\x14UPDATE_NOT_AVAILABLE\x10\x01\x12\x12\n\rUNKNOWN_ERROR\x10\xff\x01\"\x99\x01\n\x13UpdateFirmwareStart\x12\x36\n\x06result\x18\x01 \x01(\x0e\x32&.device.api.UpdateFirmwareStart.Result\"J\n\x06Result\x12\x12\n\x0eUPDATE_STARTED\x10\x00\x12\x18\n\x14UPDATE_NOT_AVAILABLE\x10\x01\x12\x12\n\rUNKNOWN_ERROR\x10\xff\x01\x42\x10\n\x06\x64\x65viceB\x06updateb\x06proto3')



_UPDATEFIRMWARECHECK = DESCRIPTOR.message_types_by_name['UpdateFirmwareCheck']
_UPDATEFIRMWARESTART = DESCRIPTOR.message_types_by_name['UpdateFirmwareStart']
_UPDATEFIRMWARECHECK_RESULT = _UPDATEFIRMWARECHECK.enum_types_by_name['Result']
_UPDATEFIRMWARESTART_RESULT = _UPDATEFIRMWARESTART.enum_types_by_name['Result']
UpdateFirmwareCheck = _reflection.GeneratedProtocolMessageType('UpdateFirmwareCheck', (_message.Message,), {
  'DESCRIPTOR' : _UPDATEFIRMWARECHECK,
  '__module__' : 'updatefirmware_pb2'
  # @@protoc_insertion_point(class_scope:device.api.UpdateFirmwareCheck)
  })
_sym_db.RegisterMessage(UpdateFirmwareCheck)

UpdateFirmwareStart = _reflection.GeneratedProtocolMessageType('UpdateFirmwareStart', (_message.Message,), {
  'DESCRIPTOR' : _UPDATEFIRMWARESTART,
  '__module__' : 'updatefirmware_pb2'
  # @@protoc_insertion_point(class_scope:device.api.UpdateFirmwareStart)
  })
_sym_db.RegisterMessage(UpdateFirmwareStart)

if _descriptor._USE_C_DESCRIPTORS == False:

  DESCRIPTOR._options = None
  DESCRIPTOR._serialized_options = b'\n\006deviceB\006update'
  _UPDATEFIRMWARECHECK._serialized_start=37
  _UPDATEFIRMWARECHECK._serialized_end=222
  _UPDATEFIRMWARECHECK_RESULT._serialized_start=146
  _UPDATEFIRMWARECHECK_RESULT._serialized_end=222
  _UPDATEFIRMWARESTART._serialized_start=225
  _UPDATEFIRMWARESTART._serialized_end=378
  _UPDATEFIRMWARESTART_RESULT._serialized_start=304
  _UPDATEFIRMWARESTART_RESULT._serialized_end=378
# @@protoc_insertion_point(module_scope)
