<?php

class Bond_SmartGwt_Request_Http extends Bond_SmartGwt_Request {
	public function __construct() {
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$params = $_POST;
		}
		else {
			$params = $_GET;
		}
		
		$this->fromArray($params);
	}
}