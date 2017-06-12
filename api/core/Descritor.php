<?php 
	namespace NSFW;
	use Imagick;

	class Descritor 
	{
		public static function extrairDados(Imagick $imagem) 
		{
			/* O descritor é um codigo proprietario, voce deve implementar o seu proprio */
			$dados = Array();
			$dados[] = 1;
			$dados[] = 2;
			$dados[] = 3;
			return $dados;
		}
	}