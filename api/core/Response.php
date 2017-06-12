<?php 
	namespace NSFW;

	use PDO;
	use DateTime;
	use DateTimeZone;
	use Exception;

	class Response 
	{
		public static function addCustomHeader(string $name, string $value)
		{
			header($name.': '.$value);
		}

		public static function parseHeaders($r)
		{
		    $o = array();
		    $r = substr($r, stripos($r, "\r\n"));
		    $r = explode("\r\n", $r);
		    foreach ($r as $h) {
		    	if($h){
			        list($v, $val) = explode(": ", $h);
			        if ($v == null) continue;
			        $o[$v] = $val;
		    	}
		    }
		    return $o;
		}

		public static function detectIsNSFWImage($path)
		{
			$redirect = true;
			$dontRedirect = '&porn-block-show-dont-redirect=1';
			$dontRedirectLength = strlen($dontRedirect) * -1;

			$image = false;

			if(substr($path,$dontRedirectLength) === $dontRedirect){
				$redirect = false;
			}

			if(!$redirect){
				$path = substr($path,0,strlen($path)+$dontRedirectLength);
				if(substr($path,-1,1) === '?'){
					$path = substr($path,0,strlen($path)-1);
				}
			}

			$forceShow = '?&porn-block-show-image=1';
			$forceShowLenght = strlen($forceShow) * -1;
			$forceBlock = '?&porn-block-force-hidden=1';
			$forceBlockLength = strlen($forceBlock) * -1;

			if(substr($path,$forceBlockLength) === $forceBlock){
				return Array(
					'scale' => 0,
					'redirect' => $redirect,
					'force' => true,
					'image' => $image,
					'valid' => false,
					'url' => substr($path,0,strlen($path) + $forceBlockLength)
				);
			}

			if(substr($path,$forceShowLenght) === $forceShow){
				return Array(
					'scale' => 0,
					'force' => true,
					'redirect' => $redirect,
					'valid' => true,
					'image' => $image,
					'url' => substr($path,0,strlen($path) + $forceShowLenght)
				);
			}

			$novaUrl = $path;
			$md5Url = md5($novaUrl);
			try{

				$banco = new BancodeDados();
				$banco->executarSQL('select * from images_caches_url where url_md5 = ? and cache_max_date > now() limit 1',Array($md5Url));
				$resultado = $banco->fetchAll(PDO::FETCH_ASSOC);
				if($resultado){
					$resultado = $resultado[0];
					$retorno = Array(
						'scale' => (float)$resultado['confianca'],
						'redirect' => $redirect,
						'valid' => !(bool)$resultado['nsfw'],
						'url' => $novaUrl,
						'image' => $image
					);	
					return $retorno;
				}else{
					$ch = curl_init($novaUrl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_VERBOSE, 0);
					curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,2); 
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);
					$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					$response = curl_exec($ch);
					$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$header = substr($response, 0, $header_size);
					$header = self::parseHeaders($header);
					$maxDate = isset($header['Expires']) ? new \DateTime($header['Expires']) : null;
					$maxDate->setTimezone(new \DateTimeZone('UTC'));
					$maxDate = $maxDate->format('Y-m-d H:i:s');

					
					$image = new \Imagick();
					$image->readImageBlob(substr($response, $header_size));
					
					$assinatura = TratamentoImagem::getImageSignature($image);

					$banco->executarSQL('select * from images_results where assinatura = ? limit 1',Array($assinatura));
					$resultado = $banco->fetchAll(PDO::FETCH_ASSOC);
					if($resultado){
						$resultado = $resultado[0];
						$banco->executarSQL('insert into images_caches_url values(null,?,?,?,?,now(),?) on duplicate key update cache_max_date = values(cache_max_date), nsfw = values(nsfw); ',Array(
			            	$novaUrl,
			            	$md5Url,
			            	$maxDate,
			            	(int)$resultado['nsfw'],
			            	$resultado['confianca']
			            ));
			            $retorno = Array(
							'scale' => (float)$resultado['confianca'],
							'redirect' => $redirect,
							'valid' => !(bool)$resultado['nsfw'],
							'url' => $novaUrl,
							'image' => $image
						);	
						return $retorno;
					}
				}

				$width = $image->getImageWidth();
	            $height = $image->getImageHeight();
	            $minWidth = 30;
	            $minHeight = 30;
	            if($width > $minWidth && $height > $minHeight){
	            	
	            	$dadosDescritor = Descritor::extrairDados($image);
	            	if(!empty($dadosDescritor)){
	            		
	            		$md5Assinatura = md5($assinatura);
	            		$banco->executarSQL('insert into files values (null,?,?,?) on duplicate key update name = values(name) ',Array(
	            			$assinatura,$assinatura,$md5Assinatura
	            		));

	            		$fileId = $banco->lastInsertId();
	            		$fileId = (int) $fileId;
	            		if(!is_numeric($fileId) || $fileId <= 0){
	            			 $banco->executarSQL('select id from files where md5 = ? ',$md5Assinatura);
							$fileId = $banco->fetchAll();
							if($fileId){
								$fileId = $fileId[0][0];
							}else{
								throw new Exception('Invalida file ID',1);
							}
	            		}

	            		$banco->executarSQL('insert into images values (null,?,?,?,?) on duplicate key update name = values(name) ',Array(
	            			100,
							$fileId,
							$assinatura,
							99999	            			
	            		));

	            		$imageId = $banco->lastInsertId();
	            		$imageId = (int) $imageId;
	            		if(!is_numeric($imageId) || $imageId <= 0){
	            			$banco->executarSQL('select id from images where file_id = ? ',$fileId);
							$imageId = $banco->fetchAll();
							if($imageId){
								$imageId = $imageId[0][0];
								$banco->executarSQL('delete from bag_of_words where image_id = ?',$imageId);
	            				$banco->executarSQL('delete from images_descriptors_by_algorithms where image_id = ?',$imageId);
							}else{
								throw new Exception('Invalida file ID',1);
							}
	            		}

	            		foreach($dadosDescritor as &$i)
	            		{
	            			$i = '('.$imageId.','.(float)$i.')';
	            		}
	            		$dadosDescritor = implode(',',$dadosDescritor);
	            		$banco->executarSQL('insert into bag_of_words (image_id,data) values '.$dadosDescritor);
	            		$SocketClient = new SocketClient();
	            		$response = $SocketClient->isNSFW($imageId);
	            		unset($SocketClient);
	            		$retorno = Array(
							'scale' => $response[1],
							'redirect' => $redirect,
							'valid' => !$response[0],
							'url' => $novaUrl,
							'image' => $image
						);	
	            	}else{
		            	$retorno = Array(
							'scale' => 0,
							'redirect' => $redirect,
							'valid' => false,
							'url' => $novaUrl,
							'image' => $image
						);	
	            	}
	            }else{
	            	$retorno = Array(
						'scale' => 0.5,
						'redirect' => $redirect,
						'valid' => true,
						'url' => $novaUrl,
						'image' => $image
					);	
	            }

			}catch(\Exception $e){
				return self::catchResponse($e);
			}
		
            $banco->executarSQL('insert into images_caches_url values(null,?,?,?,?,now(),?) on duplicate key update cache_max_date = values(cache_max_date), nsfw = values(nsfw); ',Array(
            	$novaUrl,
            	$md5Url,
            	$maxDate,
            	(int)!$retorno['valid'],
            	$retorno['scale']
            ));

            $banco->executarSQL('insert into images_results values(null,?,?,now(),?) on duplicate key update nsfw = values(nsfw) ',Array(
            	$assinatura,
            	(int)!$retorno['valid'],
            	$retorno['scale']
            ));

            return $retorno;
		}

		public static function filterImage(string $path,string $requestURI, Array $filters)
		{
			$image_url = trim(substr($requestURI,strlen($path)));
			if($image_url){
				$url = self::detectIsNSFWImage($image_url);
				if($url['valid']){
					$filters = Array();
					self::addCustomHeader('X-NSFW','false');
					if($url['redirect']){
						header('Location: '.$url['url']);
						exit;
					}
					$filters['texto'] = false;
				}else{
					self::addCustomHeader('X-NSFW','true');
				}
			}else{
				exit;			
			}
   			
			try{
			    
			    if( $url['image'] ){
			    	$TratamentoImagem = new TratamentoImagem( $url['url'], $filters, $url['image'] );
			    }else{
			    	$TratamentoImagem = new TratamentoImagem( $url['url'], $filters );
				}

			    $TratamentoImagem->exibir();

			} catch( Exception $e ) {
				return self::catchResponse($e);
			}

			exit;	
		}

		public static function catchResponse(Exception $e)
		{
				// nao exibir, pq nao conseguimos processar
				exit;
				if(!isset($largura)){
					$largura = 100;
				}else{
					if($largura <= 0){
						$largura = 100;
					}
				}
				if(!isset($altura)){
					$altura = 100;
				}else{
					if($altura <= 100){
						$altura = 100;
					}
				}

				header('Content-type: image/jpeg');
		        $img = imagecreate($largura, $altura);
		        imagecolorallocate($img, 204, 204, 204);
		        $c = imagecolorallocate($img, 153, 153, 153);
		        $c1 = imagecolorallocate($img, 0, 0, 0);
		        imageline($img, 0, 0, $largura, $altura, $c);
		        imageline($img, $largura, 0, 0, $altura, $c);
		        imagestring($img, 2, 5, $altura / 2, 'Image not found', $c1);
		        imagejpeg($img);
		        imagedestroy($img);
		}
	}