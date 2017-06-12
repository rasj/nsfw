<?php 
	include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';

	class FeedbackResponse {

		private $dados;

		private $save = false;

		private $userId;

		private $conn;

		public function __construct(Array $dados, $userId)
		{
			$this->dados = $dados;
			$this->userId = $userId;
			$this->conn = new \PDO('mysql:host='._DATABASE_HOST_.';port='._DATABASE_PORT_.';dbname='._DATABASE_NAME_.';charset=utf8', _DATABASE_USER_,_DATABASE_PASSWORD_);
		}

		public function save()
		{
			if($this->save){
				return true;
			}
			$this->save = true;

			$urls = isset($this->dados['url']) ? $this->dados['url'] : Array();
    
		    if(!is_array($urls)){
		      $urls = Array();
		    }
		    
		    $certoErrado = isset($this->dados['certo_errado']) ? $this->dados['certo_errado'] : Array();
		    if(!is_array($certoErrado)){
		      $certoErrado = Array();
		    }

		    $feedbackResponses = Array();
		    foreach($urls as $i => $url)
		    {
		      if(isset($certoErrado[$i])){
		        $feedbackResponses[$url] = $certoErrado[$i];
		      }
		    }

		    $conn = $this->conn;
		    $requestId = uniqid();
		    
		    $userId = $this->getUserId($this->userId);
		    $msg = isset($this->dados['mensagem']) ? $this->dados['mensagem'] : '';
		    $msg = trim((string)$msg);
		    if($userId && $msg){
		    	$prepare = $conn->prepare("INSERT INTO users_comment (user_id, request_id, comment) VALUES (:user_id,:request_id,:comment)");
	    		$prepare->bindParam(':user_id', $userId);
	    		$prepare->bindParam(':comment', $msg);
			    $prepare->bindParam(':request_id', $requestId);
			    $prepare->execute();
		    }
		    if($userId && !empty($feedbackResponses)){
		    	foreach($feedbackResponses as $url => $status){
		    		$prepare = $conn->prepare("INSERT INTO users_feedback (user_id, content_id, correct, created_at,request_id) VALUES (:user_id,:content_id,:correct,now(),:request_id)");
		    		$contentId = $this->getContentId($url);
		    		$status = $status == 1 ? 1 : 0;
		    		$prepare->bindParam(':user_id', $userId);
				    $prepare->bindParam(':content_id', $contentId);
				    $prepare->bindParam(':correct', $status);
				    $prepare->bindParam(':request_id', $requestId);
				    $prepare->execute();
		    	}
		    }

		    return true;
		}

		private function getContentId($url)
		{
			$conn = $this->conn;
			return $url;
		}

		private function getUserId($cookieId, $insert = true)
		{
			$conn = $this->conn;
			$prepare = $conn->prepare('select * from users where cookie_id = :cookie_id limit 1');
			$prepare->bindParam(':cookie_id',$cookieId);
			$prepare->execute();
			$dados = $prepare->fetch(\PDO::FETCH_ASSOC);
			if(!$dados && $insert){
				$prepare = $conn->prepare('insert into users (cookie_id) values(:cookie_id)');
				$prepare->bindParam(':cookie_id',$cookieId);
				$prepare->execute();
				return $this->getUserId($cookieId,false);
			}
			if(is_numeric($dados['id'])){
				return $dados['id'];
			}
			return false;
		}


	}