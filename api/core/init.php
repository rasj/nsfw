<?php
	namespace NSFW;
	
	error_reporting(E_ALL);
	ini_set('display_errors',0);

	include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
	include __DIR__.DIRECTORY_SEPARATOR.'BancoDados.php';
	include __DIR__.DIRECTORY_SEPARATOR.'Response.php';
	include __DIR__.DIRECTORY_SEPARATOR.'SocketClient.php';
	include __DIR__.DIRECTORY_SEPARATOR.'Descritor.php';
	include __DIR__.DIRECTORY_SEPARATOR.'TratamentoImagem.php';

	header("Pragma: public");

	$requestURI = $_SERVER['REQUEST_URI'];

	Response::filterImage('/',$requestURI,Array(
   		'sepia' => 0,
   		'blur' => 35,
   		'texto' => true,
   		'cache' => false,
   		'grayscale' => 10
   	));