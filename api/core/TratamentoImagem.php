<?php
    namespace NSFW;

    class TratamentoImagem {
        
        private $img = false;
        private $props = Array();

        private $cache = false;
        private $cacheFile = false;
        private $cacheFileToSave = false;
        private $signature;
        private $foldercache = __DIR__.DIRECTORY_SEPARATOR.'cache';
        private $filters;

        private function calculateSignature()
        {
            return md5(json_encode($this->props));
        }
    
        public function __construct( $arquivo, Array $filters = Array(), \Imagick $imagick = null )
        {   
            $this->props['file'] = $arquivo;
            $extension = 'png';
            $this->props['extension'] = $extension;
            $this->props['blur'] = (int)(isset($filters['blur']) ? $filters['blur'] : 0);
            if($this->props['blur'] > 100){
                $this->props['blur'] = 100;
            }
            $this->filters = $filters;
            if($this->props['blur'] < 0){
                $this->props['blur'] = 0;
            }
            
            $this->props['texto'] = $this->filters['texto'] ?? true;

            $this->props['sepia'] = (int)($filters['sepia'] ?? 0);
            if($this->props['sepia'] > 100){
                $this->props['sepia'] = 100;
            }
            if($this->props['sepia'] < 0){
                $this->props['sepia'] = 0;
            }
            $this->props['grayscale'] = (int)($filters['grayscale'] ?? 0);
            if($this->props['grayscale'] > 100){
                $this->props['grayscale'] = 100;
            }
            if($this->props['grayscale'] < 0){
                $this->props['grayscale'] = 0;
            }

            $this->cache = (bool)($filters['cache'] ?? false);
            $this->cache = true;
            if( $this->cache  ){
                $this->signature = $this->calculateSignature();

                $realFile = $this->foldercache.DIRECTORY_SEPARATOR.$this->signature.'.'.$extension;

                if(file_exists($realFile)){
                    $this->cacheFile = $realFile;
                }else{
                    $this->cacheFileToSave = $realFile;
                }
            }

            if($imagick && !$this->cacheFile){
                $this->img = $imagick;
            }
            return $this;
        }
        
        private function getImage()
        {
            if(!$this->img){
                if($this->cacheFile){
                    $this->img = new \Imagick($this->cacheFile);
                }else{
                    $this->img = new \Imagick($this->props['file']);
                }
            }
            return $this->img;
        }

        private function exibirCache()
        {
            return $this->response($this->getImage());
        }

        public function exibir(  )
        {
            $image = $this->getImage();
            if($this->cacheFile){
                return $this->exibirCache();
            }
            $image->setImageCompression(\Imagick::COMPRESSION_ZIP);
            $image->setImageCompressionQuality(80);
            $image->stripImage();

            if($this->props['blur'] > 0){
                $taxa = $this->props['blur'] / 10;
                $image->blurImage(5 * $taxa,3 * $taxa);
            }

            if($this->props['sepia'] > 0 )
            {
                $image->sepiaToneImage(80);
            }
            if($this->props['grayscale']){
                $image->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
		if($image->getImageAlphaChannel()){
                	$image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
		}
            }else{
                $image->setImageType(\Imagick::IMGTYPE_OPTIMIZE);    
            }

            $image->setImageInterlaceScheme(\Imagick::INTERLACE_PLANE);
            $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);


            if($this->props['texto']){
                /* Text to write */
               /* Black text */
                $draw = new \ImagickDraw();
                $pixel = new \ImagickPixel( 'gray' );
                $draw->setFillColor('black');

                /* Font properties */
                $draw->setFont(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'Arial.ttf');
                $draw->setFontSize( 30 );

                /* Create text */
                $image->annotateImage($draw, 10, 45, 0, 'Imagem bloqueada');
                $image->setImageFormat('png');
            }


            return $this->response($image,$this->cache);
        }

        public static function getImageSignature(\Imagick $image)
        {
            return $image->getImageSignature();
        }

        private function response($image,$save = false)
        {
            $blob = null;
            if($save){
                if( $this->cacheFileToSave ){
                    $blob = $image->getImageBlob();
                    file_put_contents($this->cacheFileToSave, $blob);
                }
            }
            if(!$blob){
                $blob = $image->getImageBlob();   
            }
            header('Content-type: '.$image->getImageMimeType());
            Response::addCustomHeader('X-Img-Extension',$image->getImageFormat());
            Response::addCustomHeader('X-Img-Height',$image->getImageHeight());
            Response::addCustomHeader('X-Img-Width',$image->getImageWidth());
            Response::addCustomHeader('X-Img-Size',strlen($blob));
            Response::addCustomHeader('X-Img-Signature',self::getImageSignature($image));
            echo $blob;
            unset($image);
            exit;
        }
        
    }
