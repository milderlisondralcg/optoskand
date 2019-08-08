<?php

class Media extends Database {

	public $conn;
	protected $ts = 0;

	function __construct() {
		$this->conn = parent::__construct(); // get db connection from Database model
		$this->ts = date( "Y-m-d H:i:s", time() ); // set current timestamp
	}
}

?>