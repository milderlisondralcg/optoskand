<?php

class Access extends Database {

	public $conn;
	protected $ts = 0;
	protected $portal_access = "portal_access";
	protected $portal_keys = "portal_keys";

	function __construct() {
		$this->conn = parent::__construct(); // get db connection from Database model
		$this->ts = date( "Y-m-d H:i:s", time() ); // set current timestamp
	}

	protected $check_key = "check_key";
	

	public function check_key($my_access_key){

		$query = "SELECT `access_key` , `active`, `id_portal_keys` FROM `".$this->portal_keys."` WHERE `access_key` = :Get_access_Key";
		$stmt = $this->conn->prepare($query);
		$stmt-> bindValue(':Get_access_Key',trim($my_access_key), PDO::PARAM_STR);
		$stmt-> execute();	
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		//return  $result['id_portal_keys'];
		if( $stmt->rowCount() == 1 ){
			return true;
		}else{
			return false;
		}
		//return $result;
		
		/*if( count($result) > 0 ){
	
			 . $access_key;
		}else{
			return 'bad' . $result . $access_key;
		}	*/
	}

	/**
	* Add new user access
	* @param array $data
	* @return boolean $result
	*/
	public function add($data){
		extract($data);
		$cdt = date("Y-m-d H:i:s");
		$user_ip = $_SERVER['REMOTE_ADDR'];
		$stmt = $this->conn->prepare("INSERT INTO `".$this->portal_access."` (access_ip, access_email,access_key,access_uuid_1, access_granted_datetime,category) VALUES (:access_ip,:access_email, :access_key,:access_uuid_1,:access_granted_datetime,:category)");
		$stmt->bindParam(':access_ip',$user_ip, PDO::PARAM_STR);
		$stmt->bindParam(':access_email',$email, PDO::PARAM_STR);
		$stmt->bindParam(':access_key',$passcode, PDO::PARAM_STR);
		$stmt->bindParam(':access_uuid_1',$access_uuid_1, PDO::PARAM_STR);
		$stmt->bindParam(':access_granted_datetime',$cdt, PDO::PARAM_STR);
		$stmt->bindParam(':category',$category, PDO::PARAM_STR);
		
		if($stmt->execute()){
			
			$response['result'] = true;
			$response['id'] = $this->conn->lastInsertId();
		}else{
			$response['result'] = false;
		}
		return $response;
	}
	
	/*
	* verify_auth
	* Verify the user requesting access via the user's ip address, category, and unique access id
	* @param $data array
	* @return boolean $result
	*/
	public function verify_auth($data){
		extract($data);
		$user_ip = $_SERVER['REMOTE_ADDR'];
		$query = "SELECT `access_ip`,`access_uuid_1`,`category`,`access_granted_datetime` FROM `".$this->portal_access."` WHERE `access_ip` = :access_ip AND `access_uuid_1` = :access_uuid_1 AND `category` = :category";

		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':access_ip',$user_ip, PDO::PARAM_STR);
		$stmt->bindValue(':access_uuid_1',$access_uuid_1, PDO::PARAM_STR);
		$stmt->bindValue(':category',$category, PDO::PARAM_STR);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if( count($result) > 0 ){
			
			// check to see if access_granted_datetime was less than 24 hours ago
			$t1 = strtotime( $result['access_granted_datetime'] );
			$t2 = strtotime( date("Y-m-d H:i:s") ); // current datetime
			$diff = $t2 - $t1;
			$hours = $diff / ( 60 * 60 );

			if( $hours < 24 ){
				return true;
			}
		}
		/*
		$stmt->execute();
		$number_of_rows = $stmt->fetchColumn();
		return $number_of_rows;
		*/
					
	}

	
}



?>