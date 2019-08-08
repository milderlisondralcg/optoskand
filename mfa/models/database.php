<?php

class Database{

	public $conn;
	
	protected function __construct(){
		$this->conn = null;
		
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, "MYSQLCONNSTR_localdb") !== 0) {
				continue;
			}			
			$this->db_host = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
			$this->db_name = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
			$this->db_user = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
			$this->db_pass = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);

		}		

        try{
            $this->conn = new PDO("mysql:host=" . $this->db_host . ";dbname=" . $this->db_name, $this->db_user, $this->db_pass);
            $this->conn->exec("set names utf8");
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
 
        return $this->conn;
	}


}
