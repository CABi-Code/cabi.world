#!/usr/bin/env python3
import socket
import json
import sys
import struct

def pack_varint(value):
    buffer = bytearray()
    while True:
        byte = value & 0x7F
        value >>= 7
        buffer.append(byte | (0x80 if value else 0))
        if value == 0:
            break
    return bytes(buffer)

def unpack_varint(sock):
    value = 0
    size = 0
    while True:
        byte = sock.recv(1)[0]
        value |= (byte & 0x7F) << (7 * size)
        size += 1
        if not (byte & 0x80):
            break
    return value

if len(sys.argv) < 2:
    print("Usage: ./mc-ping.py <host> [port]")
    sys.exit(1)

host = sys.argv[1]
port = int(sys.argv[2]) if len(sys.argv) > 2 else 25565

sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
sock.settimeout(10)
try:
    sock.connect((host, port))

    # Handshake packet (next state 1 = status)
    protocol_version = 767  # 1.21; 772=1.21.8, 765=1.20.6, см. https://wiki.vg/Protocol_version_numbers
    addr_bytes = host.encode('utf-8')
    handshake_data = (
        b'\x00' +  # Packet ID
        pack_varint(protocol_version) +
        pack_varint(len(addr_bytes)) + addr_bytes +
        struct.pack('>H', port) +  # Unsigned short port
        pack_varint(1)  # Next state: status
    )
    sock.send(pack_varint(len(handshake_data)) + handshake_data)

    # Status Request packet
    status_request = b'\x00'
    sock.send(pack_varint(1) + status_request)

    # Read response
    packet_len = unpack_varint(sock)
    packet_id = unpack_varint(sock)
    json_len = unpack_varint(sock)
    response = sock.recv(json_len).decode('utf-8')
    data = json.loads(response)
    
    print(packet_len)
    print(packet_id)
    print(response)
    
    print("✅ Сервер **ONLINE**")
    print(f"📢 MOTD: {data.get('description', {}).get('text', 'N/A')}")
    print(f"🔢 Версия: {data.get('version', {}).get('name', 'N/A')} (proto {data.get('version', {}).get('protocol', 'N/A')})")
    print(f"👥 Игроки: {data.get('players', {}).get('online', 0)} / {data.get('players', {}).get('max', 0)}")
    if 'sample' in data.get('players', {}):
        print(f"📋 Примеры игроков: {', '.join([p['name'] for p in data['players']['sample']])}")
    print(f"🖼️ Favicon: {'Да' if 'favicon' in data else 'Нет'}")

except Exception as e:
    print(f"❌ Ошибка: {e} (оффлайн, неверный порт или firewall)")
finally:
    sock.close()