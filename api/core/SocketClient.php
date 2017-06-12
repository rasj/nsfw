<?php 
	namespace NSFW;

	class SocketClient {

		private $socket;

		public function __construct()
		{
			$clusters = _SOCKET_CLUSTERS_;
			shuffle($clusters);
			$this->socket = socket_create(AF_INET,SOCK_STREAM,0) or die("Cannot create a socket");
			socket_connect($this->socket,$clusters[0][0],$clusters[0][1]) or die("Could not connect to the socket");
		}

		private function getClasseConfianca(int $idImagem)
		{
			socket_write($this->socket,json_encode(Array(
				'classificar',
				$idImagem
			)));
			$buf = socket_read($this->socket, 1024);
			return [$buf,1];
		}
		
		public function isNSFW(int $idImagem)
		{
			$resultado = $this->getClasseConfianca($idImagem);
			$class = $resultado[0];
			$confianca = $resultado[1];
			if($class == 'vNonPorn'){
				return [false,$confianca];
			}
		    return [true,$confianca];
		}

		public function __destruct()
		{
			socket_close($this->socket);
		}
	}