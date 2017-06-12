import socket
import os
import sys
import json
import time
import asyncore
import threading

SOCKETTIME=0.1
HOST = '0.0.0.0'              # Endereco IP do Servidor
PORT = 5000            # Porta que o Servidor esta
if len(sys.argv) > 1:
	PORT = sys.argv[1]
	if not PORT.isdigit():
		PORT = 5000

PORT = int(PORT)

class Server(asyncore.dispatcher):

    def __init__(self, host, port):
        asyncore.dispatcher.__init__(self)
        self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
        self.set_reuse_addr()
        self.bind((host, port))
        self.listen(5)

    def handle_accept(self):
        pair = self.accept()
        if pair is not None:
            sock, addr = pair
            handler = ServerHandler(sock)

class ServerHandler(asyncore.dispatcher_with_send):

    def listenToClient(self):
        msg=""
        try:
            while True:
                _buffer = self.recv(1024)
                if not _buffer: break
                msg+=_buffer    
        except Exception as inst:
            pass

        if msg:
            parameters = json.loads(msg)
            if parameters[0] == 'classificar':
                imagemID = parameters[1]
                CLASSE='vPorn'
                self.send(CLASSE)
            else:
                # REGERAR MODELO
                pass

    def handle_read(self):
        self.settimeout(SOCKETTIME)
        threading.Thread(target = self.listenToClient).start()

server = Server(HOST, PORT)
asyncore.loop()

