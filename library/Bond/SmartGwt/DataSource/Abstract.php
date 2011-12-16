<?php

abstract class Bond_SmartGwt_DataSource_Abstract implements Bond_SmartGwt_DataSource_Interface {
	protected $_options = array();
	/**
	 * @var Bond_SmartGwt_Request
	 */
	protected $_request;
	
	public function __construct($opts = null) {
		if(is_array($opts)) {
			$this->setOptions($opts);
		}
	}
	
	protected function setOptions(array $opts) {
		$this->_options = $opts;
	}
	
	public function getOption($opt) {
		return $this->_options[$opt];
	}
	
	protected function setRequest(Bond_SmartGwt_Request $request) {
	    $this->_request = $request;
	}
	
	/**
	 * @return Bond_SmartGwt_Request
	 */
	protected function getRequest() {
	    return $this->_request;
	}
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function handle(Bond_SmartGwt_Request $request) {
		$this->setRequest($request);
		
		switch($request->getOperationType()) {
			case Bond_SmartGwt_Request::TYPE_FETCH:
				$response = $this->fetch($request);
				break;
			case Bond_SmartGwt_Request::TYPE_UPDATE:
				$response = $this->update($request);
				break;
			case Bond_SmartGwt_Request::TYPE_ADD:
				$response = $this->add($request);
				break;
			case Bond_SmartGwt_Request::TYPE_REMOVE:
				$response = $this->remove($request);
				break;
			case Bond_SmartGwt_Request::TYPE_CUSTOM:
                $response = $this->custom($request);
                break;
			default:
				throw new Zend_Exception('N/I');
				
		}
		
		return $response;
	}
	
	public function fetch(Bond_SmartGwt_Request $request) {
		throw new Zend_Exception("N/I");
	}
	
	public function update(Bond_SmartGwt_Request $request) {
		throw new Zend_Exception("N/I");
	}
	
	public function add(Bond_SmartGwt_Request $request) {
		throw new Zend_Exception("N/I");
	}
	
	public function remove(Bond_SmartGwt_Request $request) {
		throw new Zend_Exception("N/I");
	}
	
    public function custom(Bond_SmartGwt_Request $request) {
        throw new Zend_Exception("N/I");
    }
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response
	 */
	protected function _createResponse(Bond_SmartGwt_Request $request) {
		$response = new Bond_SmartGwt_Response();
		$response->setStatus(0);
		$response->setStartRow($request->getStartRow());
		
		return $response;
	}
	
	protected function _validateRow(array $row) {
		return array();
	}
	
	protected function _getData(Bond_SmartGwt_Request $request) {
		return $this->_sanitazeRequestValues($request->getRawData());
	}
	
	protected function _sanitazeRequestValue($field, $value) {
		if($value === true) {
			return 1;
		}
		else if($value === false) {
			return 0;
		}
		
		switch($value) {
			//case strlen($value) === 0:
			case 'null':
				$value = null;
				break;
			case 'true':
				$value = 1;
				break;
			case 'false':
				$value = 0;
				break;
		}
		
		return $value;
	}
	
	protected function _sanitazeResponseValue($field, $value) {
		return $value;
	}
	
	protected function _sanitazeRequestValues(array $values) {
		$data = array();
		foreach($values as $field => $value) {
			$data[$field] = $this->_sanitazeRequestValue($field, $value);
		}
		
		return $data;
	}
	
	protected function _sanitazeResponseValues(array $values, $origObject = null) {
		$data = array();
		foreach($values as $field => $value) {
			$data[$field] = $this->_sanitazeResponseValue($field, $value);
		}
		
		return $data;
	}
	
}