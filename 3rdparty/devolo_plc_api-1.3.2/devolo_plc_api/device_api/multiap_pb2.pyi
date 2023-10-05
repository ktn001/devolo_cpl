"""
@generated by mypy-protobuf.  Do not edit manually!
isort:skip_file
"""
import builtins
import google.protobuf.descriptor
import google.protobuf.message
import sys

if sys.version_info >= (3, 8):
    import typing as typing_extensions
else:
    import typing_extensions

DESCRIPTOR: google.protobuf.descriptor.FileDescriptor

@typing_extensions.final
class WifiMultiApGetResponse(google.protobuf.message.Message):
    """Details about MultiAP as returned by the 'WifiMultiApGet' endpoint."""

    DESCRIPTOR: google.protobuf.descriptor.Descriptor

    ENABLED_FIELD_NUMBER: builtins.int
    CONTROLLER_ID_FIELD_NUMBER: builtins.int
    CONTROLLER_IP_FIELD_NUMBER: builtins.int
    enabled: builtins.bool
    """Describes if the MultiAP functionality is enabled in the device."""
    controller_id: builtins.str
    """The id of the mesh controller, in form of its MAC address,
    if a mesh controller is known to the device.
    If the device is not aware of a mesh controller, e.g. because
    none has been elected yet, it is left empty.

    The MAC address is represented as a string of 12 hexadecimal
    digits (digits 0-9, letters A-F or a-f) displayed as six pairs of
    digits separated by colons.
    """
    controller_ip: builtins.str
    """The IP address of the known mesh controller, if the implementation
    provides it.
    If the device is not aware of a mesh controller or doesn't
    know its IP, it is left empty.

    The IP can be an IPv4 in dot-separated decimal format, or an IPv6
    in colon-separated hexadecimal format. In case multiple IPs are
    known, the value can be a comma-separated string of either formats.
    Also, an IP can optionally be prefixed with an identifier separated
    from the IP with a semicolon.
    """
    def __init__(
        self,
        *,
        enabled: builtins.bool = ...,
        controller_id: builtins.str = ...,
        controller_ip: builtins.str = ...,
    ) -> None: ...
    def ClearField(self, field_name: typing_extensions.Literal["controller_id", b"controller_id", "controller_ip", b"controller_ip", "enabled", b"enabled"]) -> None: ...

global___WifiMultiApGetResponse = WifiMultiApGetResponse