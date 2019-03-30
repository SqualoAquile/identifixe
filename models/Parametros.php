<?php
class Parametros extends model {

    protected $table;
    protected $permissoes;
    
    public function __construct() {
        $this->permissoes = new Permissoes();
    }

    public function index() {

        $sql = "SHOW TABLES";
        $sql = self::db()->query($sql);
        $tabelas = $sql->fetchAll();

        $infosTabelas = [];
        foreach ($tabelas as $key => $value) {

            $sqlB = "SHOW TABLE STATUS WHERE Name='" . $value[0] . "'";
            $sqlB = self::db()->query($sqlB);
            $infoTabela = $sqlB->fetchAll();

            foreach ($infoTabela as $key => $value) {
                $infoTabela[$key]["Comment"] = json_decode($value["Comment"], true);
            }

            array_push($infosTabelas, $infoTabela);
        }

        return $infosTabelas;
    }

    public function listar($request) {
        
        $this->table = $request["tabela"];

        $value_sql = "";
        if ($request["value"] && $request["campo"]) {

            $value = trim($request["value"]);
            $value = addslashes($value);
            
            $campo = trim($request["campo"]);
            $campo = addslashes($campo);

            $value_sql = " AND " . $campo . " LIKE '%" . $value . "%'";
        }

        $sql = "SELECT * FROM " . $this->table . " WHERE situacao = 'ativo'" . $value_sql;

        $sql = self::db()->query($sql);
        
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDoiscampos($request) {
        
        $this->table = $request["tabela"];

        $value_sql1 = "";
        if ($request["value1"] && $request["campo1"]) {

            $value1 = trim($request["value1"]);
            $value1 = addslashes($value1);
            
            $campo1 = trim($request["campo1"]);
            $campo1 = addslashes($campo1);

            $value_sql1 = " AND " . $campo1 . " LIKE '%" . $value1 . "%'";
        }

        $value_sql2 = "";
        if ($request["value2"] && $request["campo2"]) {

            $value2 = trim($request["value2"]);
            $value2 = addslashes($value2);
            
            $campo2 = trim($request["campo2"]);
            $campo2 = addslashes($campo2);

            $value_sql2 = " AND " . $campo2 . " LIKE '%" . $value2 . "%'";
        }

        $sql = "SELECT * FROM " . $this->table . " WHERE situacao = 'ativo'" . $value_sql1 . $value_sql2;

        $sql = self::db()->query($sql);
        
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionar($request) {

        $this->table = $request["tabela"];

        if ($request["value"] && $request["campo"]) {

            $value = trim($request["value"]);
            $value = addslashes($value);
            
            $campo = trim($request["campo"]);
            $campo = addslashes($campo);
        }

        $ipcliente = $this->permissoes->pegaIPcliente();
        $alteracoes = ucwords($_SESSION["nomeUsuario"])." - $ipcliente - ".date('d/m/Y H:i:s')." - CADASTRO";

        $sql = "INSERT INTO " . $this->table . " (" . $campo . ", alteracoes, situacao) VALUES ('" . $value . "', '" . $alteracoes . "', 'ativo')";
        
        self::db()->query($sql);

        return self::db()->errorInfo();
    }

    public function excluir($request, $id) {

        $this->table = $request["tabela"];

        $id = addslashes(trim($id));

        $sql = "SELECT alteracoes FROM ". $this->table ." WHERE id = '$id' AND situacao = 'ativo'";
        $sql = self::db()->query($sql);

        if ($sql->rowCount() > 0) {
            
            $sql = $sql->fetch();
            $palter = $sql["alteracoes"];

            $ipcliente = $this->permissoes->pegaIPcliente();
            $palter = $palter." | ".ucwords($_SESSION["nomeUsuario"])." - $ipcliente - ".date('d/m/Y H:i:s')." - EXCLUSÃO";

            $sqlA = "UPDATE ". $this->table ." SET alteracoes = '$palter', situacao = 'excluido' WHERE id = '$id' ";
            self::db()->query($sqlA);
        }

        return self::db()->errorInfo();
    }

    public function editar($request, $id) {
        
        $this->table = $request["tabela"];

        if ($request["value"] && $request["campo"]) {

            $value = trim($request["value"]);
            $value = addslashes($value);
            
            $campo = trim($request["campo"]);
            $campo = addslashes($campo);
        }

        $id = addslashes(trim($id));

        $sql = "UPDATE " . $this->table . " SET " . $campo . " = '" . $value . "' WHERE id='" . $id . "'";
             
        self::db()->query($sql);

        return self::db()->errorInfo();
    }

    public function pegarFixos() {

        $this->table = "parametros";
        
        $sql = "SELECT * FROM " . $this->table . " WHERE situacao = 'ativo'";
        $sql = self::db()->query($sql);
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $key => $value) {
            $result[$key]["comentarios"] = json_decode($value["comentarios"], true);
        }

        return $result;
    }

    public function editarFixos($request, $id) {
        
        $this->table = "parametros";

        $ipcliente = $this->permissoes->pegaIPcliente();
        $hist = explode("##", addslashes($request['alteracoes']));

        if(!empty($hist[1])){ 
            $alteracoes = $hist[0]." | ".ucwords($_SESSION["nomeUsuario"])." - $ipcliente - ".date('d/m/Y H:i:s')." - ALTERAÇÃO >> ".$hist[1];     
        }

        $value = "";
        if ($request["value"]) {
            $value = trim($request["value"]);
            $value = addslashes($value);
        }

        $id = addslashes(trim($id));

        $update = "UPDATE " . $this->table . " SET valor = '" . $value . "', alteracoes = '" . $alteracoes . "' WHERE id='" . $id . "'";
             
        $update = self::db()->query($update);

        $erro = self::db()->errorInfo();

        if (empty($erro[2])){
            $select = "SELECT * FROM " . $this->table . " WHERE situacao = 'ativo' AND id = '" . $id . "'";
            $select = self::db()->query($select);
            $select = $select->fetch(PDO::FETCH_ASSOC);
        }

        return [
            "result" => $select,
            "erro" => $erro
        ];

    }

    public function parametroTamanhoBocaRolo($nomeParam){
        $result = '';

        $this->table = "parametros";
        
        $sql = "SELECT * FROM " . $this->table . " WHERE parametro = '$nomeParam' AND situacao = 'ativo'";
        $sql = self::db()->query($sql);

        if ($sql->rowCount() > 0) {

            $sql = $sql->fetch(PDO::FETCH_ASSOC);            
            $result = $sql["valor"];
        }
        
        return $result;

    }
}