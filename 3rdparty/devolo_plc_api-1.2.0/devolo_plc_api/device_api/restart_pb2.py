# -*- coding: utf-8 -*-
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: restart.proto
"""Generated protocol buffer code."""
from google.protobuf import descriptor as _descriptor
from google.protobuf import descriptor_pool as _descriptor_pool
from google.protobuf import message as _message
from google.protobuf import reflection as _reflection
from google.protobuf import symbol_database as _symbol_database
# @@protoc_insertion_point(imports)

_sym_db = _symbol_database.Default()




DESCRIPTOR = _descriptor_pool.Default().AddSerializedFile(b'\n\rrestart.proto\x12\ndevice.api\"\x80\x01\n\x0fRestartResponse\x12\x32\n\x06result\x18\x01 \x01(\x0e\x32\".device.api.RestartResponse.Result\x12\x0e\n\x06uptime\x18\x02 \x01(\x03\")\n\x06Result\x12\x0b\n\x07SUCCESS\x10\x00\x12\x12\n\rUNKNOWN_ERROR\x10\xff\x01\"#\n\x11UptimeGetResponse\x12\x0e\n\x06uptime\x18\x02 \x01(\x03\x42\x11\n\x06\x64\x65viceB\x07restartb\x06proto3')



_RESTARTRESPONSE = DESCRIPTOR.message_types_by_name['RestartResponse']
_UPTIMEGETRESPONSE = DESCRIPTOR.message_types_by_name['UptimeGetResponse']
_RESTARTRESPONSE_RESULT = _RESTARTRESPONSE.enum_types_by_name['Result']
RestartResponse = _reflection.GeneratedProtocolMessageType('RestartResponse', (_message.Message,), {
  'DESCRIPTOR' : _RESTARTRESPONSE,
  '__module__' : 'restart_pb2'
  # @@protoc_insertion_point(class_scope:device.api.RestartResponse)
  })
_sym_db.RegisterMessage(RestartResponse)

UptimeGetResponse = _reflection.GeneratedProtocolMessageType('UptimeGetResponse', (_message.Message,), {
  'DESCRIPTOR' : _UPTIMEGETRESPONSE,
  '__module__' : 'restart_pb2'
  # @@protoc_insertion_point(class_scope:device.api.UptimeGetResponse)
  })
_sym_db.RegisterMessage(UptimeGetResponse)

if _descriptor._USE_C_DESCRIPTORS == False:

  DESCRIPTOR._options = None
  DESCRIPTOR._serialized_options = b'\n\006deviceB\007restart'
  _RESTARTRESPONSE._serialized_start=30
  _RESTARTRESPONSE._serialized_end=158
  _RESTARTRESPONSE_RESULT._serialized_start=117
  _RESTARTRESPONSE_RESULT._serialized_end=158
  _UPTIMEGETRESPONSE._serialized_start=160
  _UPTIMEGETRESPONSE._serialized_end=195
# @@protoc_insertion_point(module_scope)
