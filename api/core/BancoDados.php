<?php 
    namespace NSFW;

    use \PDO;
    use \Exception;

    class BancodeDados {


        private $PDO;
        private $STMT;
        private $SQL;
        private $database;

        protected function setPDO(PDO $pdo)
        {
            $this->PDO = $pdo;
            return $this;
        }

        public function getPDO()
        {
            return $this->PDO;
        }

        private function conectarServidor($database)
        {
            try {
                $senha = _DATABASE_PASSWORD_;
                if ($database == null) {
                    $this->database = _DATABASE_NAME_;
                } else {
                    $this->database = $database;
                }
                
                $strCon = 'mysql:host='._DATABASE_HOST_.';port='._DATABASE_PORT_.';charset=UTF8' . ';dbname=' . $this->database;
                $this->PDO = new PDO($strCon, _DATABASE_USER_, $senha, array(PDO::ATTR_PERSISTENT => false));
                $this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->PDO->exec('SET time_zone = \'+00:00\'');

            } catch (PDOException $e) {
                throw new Exception("Ocorreu um erro ao conectar no banco!", 0, $e);
            }
        }

        private function conServer($database) {
            if(!$this->PDO){
                $this->conectarServidor($database);
            }
        }

        public function __construct() {
            $this->conectar(_DATABASE_NAME_);
        }

        public function conectar($database = null) {
            $this->conServer($database);
            return $this;
        }

        public function prepare($sql) {

            $this->SQL = $sql;
            $sql = trim($sql);
            $sql_palavra = substr($sql, 4);
            if ($sql_palavra == 'SELE') {
                return $this->prepareSelect($sql);
            } else if ($sql_palavra != 'CALL') {
                return $this->prepareUpdate($sql);
            } else {
                throw new Exception("Comando SQL não autorizado");
            }
            return $this;
        }

        private function prepareUpdate($sql) {
            $this->STMT = $this->PDO->prepare($sql);
            return $this;
        }

        private function prepareSelect($sql) {
            $this->STMT = $this->PDO->prepare($sql);
            return $this;
        }

        

        public function execute() {
            try{
                $this->STMT->parameters = $this->parameters;
                $this->parameters = Array();
                $this->STMT->execute();
            }catch(Exception $e){
                echo '<H1>EXCECAO</h1><Br><Br>';
                var_dump($e);
                echo '<Br><Br>';
                echo ($e->getMessage());
                echo '<Br><Br>';
                $this->debugar();
            }
            return $this;
        }

        private $parameters = Array();
        public function bindValue($parameter, $value, $data_type = NULL) {
            $this->parameters[$parameter] = $value;
            return $this->STMT->bindValue($parameter, $value, $data_type);
        }

        public function bindParam($parameter, &$variable, $data_type = NULL) {
            return $this->STMT->bindParam($parameter, $variable, $data_type);
        }

        public function bindColumn($column, &$param, $type = NULL, $maxlen = NULL, $driverdata = NULL) {
            return $this->STMT->bindColumn($column, $param, $type, $maxlen, $driverdata);
        }

        public function closeCursor() {
            return $this->STMT->closeCursor();
        }

        public function columnCount() {
            return $this->STMT->columnCount();
        }

        public function debugDumpParams() {
            $this->STMT->debugDumpParams();
        }

        public function STMT_errorCode() {
            return $this->STMT->errorCode();
        }

        public function STMT_errorInfo() {
            return $this->STMT->errorInfoCode();
        }

        public function fetch($fetch_style = NULL, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
            return $this->STMT->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        }

        public function fetchAll($fetch_style = NULL, $fetch_argument = NULL, $ctor_args = NULL) {
            if (empty($fetch_style)) {
                return $this->STMT->fetchAll();
            } else {
                if($ctor_args){
                    return $this->STMT->fetchAll($fetch_style, $fetch_argument,$ctor_args);
                }else{
                    if($fetch_argument){
                        return $this->STMT->fetchAll($fetch_style, $fetch_argument);
                    }else{
                        return $this->STMT->fetchAll($fetch_style);
                    }
                }
            }
        }

        public function fetchColumn($column_number = 0) {
            return $this->STMT->fetchColumn($column_number);
        }

        public function fetchObject($class_name = "stdClass", $ctor_args = array()) {
            return $this->STMT->fetchObject($class_name, $ctor_args);
        }

        public function getAttribute($attribute) {
            return $this->STMT->getAttribute($attribute);
        }

        public function getColumnMeta($column) {
            return $this->STMT->getColumnMeta($column);
        }

        public function nextRowset() {
            return $this->STMT->nextRowset();
        }

        public function rowCount() {
            return $this->STMT->rowCount();
        }

        public function setAttribute($attribute, $value) {
            return $this->STMT->setAttribute($attribute, $value);
        }

        public function setFetchMode($mode) {
            return $this->STMT->setFetchMode($mode);
        }

        public function beginTransaction() {
            $this->PDO->beginTransaction();
            return $this;
        }

        public function commit() {
            $this->PDO->commit();
            return $this;
        }

        public function rollback() {
            $this->PDO->rollback();
            return $this;
        }

        public function lastInsertId() {
            return $this->PDO->lastInsertId();
        }

        public function PDO_errorCode() {
            return $this->PDO->errorCode();
        }

        public function PDO_errorInfo() {
            return $this->PDO->errorInfo();
        }

        public function inTransaction() {
            return $this->PDO->inTransaction();
        }

        public function debugar(){
            $query = $this->STMT->queryString;
            $parameters = $this->STMT->parameters;
            foreach($parameters as $type => $value){
                if(is_numeric($type)){
                     $loop = 300;
                     $offset = 0;
                     $index = 1;
                     while(true){
                         $position = strpos($query,'?',$offset);
                         if(!is_numeric($position)){
                             break;
                         }
                         if($position < 0){
                             break;
                         }
                         if($loop <= 0){
                             break;
                         }
                         if($index == $type){
                             if(!is_numeric($value)){
                                 $value = '"'.  addslashes($value).'"';
                             }
                             $query = substr($query,0,$position).$value.substr($query,$position+1);
                             unset($parameters[$type]);
                         }
                         $index++;
                         $loop--;
                     }
                }else{
                    if(!is_numeric($value)){
                        $value = '"'.  addslashes($value).'"';
                    }
                    $query = strtr($query,Array(':'.$type => $value));
                    unset($parameters[$type]);
                }
            }
            if(!empty($parameters)){
                var_dump('Parametros nao setados todos --> Classe banco de dados');
                var_dump($parameters);
            }
            echo '<textarea style="width:500px;height:200px;">';
            echo $query;
            echo '</textarea><br><Br>';
            echo $query;
            var_dump($query);
            exit;
        }


        public function executarSQL($sql, $valores = '') {
            $retorno = null;
            $this->prepare($sql);
            if (is_array($valores)) {
                foreach ($valores as $indice => $valor) {
                    $this->bindValue($indice + 1, $valor);
                }
            } else {
                $this->bindValue(1, $valores);
            }
            $this->execute();
            return $retorno;
        }
    }
