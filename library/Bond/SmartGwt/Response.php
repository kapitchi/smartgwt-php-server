<?php

class Bond_SmartGwt_Response {
	//http://www.smartclient.com/smartgwtee/javadoc/com/smartgwt/client/rpc/RPCResponse.html#STATUS_VALIDATION_ERROR
	const STATUS_FAILURE = -1;
    const STATUS_LOGIN_INCORRECT = -5;
    const STATUS_LOGIN_REQUIRED = -7;
    const STATUS_LOGIN_SUCCESS = -8;
    const STATUS_MAX_LOGIN_ATTEMPTS_EXCEEDED = -6;
    const STATUS_SERVER_TIMEOUT = -100;
    const STATUS_SUCCESS = 0;
    const STATUS_TRANSPORT_ERROR = -90;
    const STATUS_VALIDATION_ERROR = -4;
    
	protected $_data = array();
	protected $_status;
	protected $_totalRows;
	protected $_startRow;
	protected $_endRow;
	protected $_errors = array();
	protected $_validationErrors = array();
	
	public function setData(array $data) {
		$this->_data = $data;
	}
	
	/**
	 * @return array
	 */
	public function getData() {
		return $this->_data;
	}
	
	protected function _sanitazeValue($value) {
		return $value;
	} 
	
	public function setStatus($status) {
		$this->_status = $status;
	}
	
	public function getStatus() {
		return $this->_status;
	}
	
	public function setTotalRows($val) {
		$this->_totalRows = $val;
	}
	
	public function getTotalRows() {
	    if($this->_totalRows === null) {
	        $this->_totalRows = count($this->getData());
	    }
		return $this->_totalRows;
	}
	
	public function setStartRow($val) {
		$this->_startRow = $val;
	}
	
	public function getStartRow() {
		return $this->_startRow;
	}
	
	public function setEndRow($val) {
		$this->_endRow = $val;
	}
	
	public function getEndRow() {
		return $this->_endRow;
	}
	
	public function getValidationErrors() {
		return $this->_validationErrors;
		//return $this->getErrors();
	}
	
	public function getErrors() {
		return $this->_errors;
	}
	
	public function setValidationErrors($errors) {
		$this->_validationErrors = $errors;
		$this->setStatus(self::STATUS_VALIDATION_ERROR);
		
		//$this->setErrors($errors, self::STATUS_VALIDATION_ERROR);
	}
	
	public function setErrors($errors, $status) {
		if(is_array($errors)) {
			$this->_errors = $errors;
		}
		elseif(is_string($errors)) {
			$this->_errors = array($errors);
		}
		else {
			$this->_errors = array();
		}
		
		$this->setStatus($status);
	}
	
	public function isError() {
		return $this->getStatus() != self::STATUS_SUCCESS;
	}
	
	public function toXml() {
		$ret = '<response>';
		$ret .= '<status>' . $this->getStatus() . '</status>';
		
		if($this->isError()) {
			if($this->getValidationErrors()) {
				$ret .= '<errors>';
				foreach($this->getValidationErrors() as $field => $msg) {
					$ret .= "<$field><errorMessage><![CDATA[$msg]]></errorMessage></$field>";
				}
				$ret .= '</errors>';
			}
			else {
				//http://www.smartclient.com/smartgwt/javadoc/com/smartgwt/client/rpc/RPCManager.html#setHandleErrorCallback(com.smartgwt.client.rpc.HandleErrorCallback)
				$ret .= "<data>";
				foreach($this->getErrors() as $error) {
					$ret .= "$error"; 
				}
				$ret .= "</data>";
			}
		}
		else {
			$ret .= '<startRow>' . $this->getStartRow() . '</startRow>';
			$ret .= '<endRow>' . $this->getEndRow() . '</endRow>';
			$ret .= '<totalRows>' . $this->getTotalRows() . '</totalRows>';
			
			$ret .= '<data>';
			foreach($this->getData() as $record) {
				$ret .= '<record>';
				
				foreach($record as $field => $value) {
					$ret .= "<$field>";
					if(is_array($value)) {
						foreach($value as $key => $val) {
							$ret .= "<$key>";
							$ret .= $this->_getXmlValue($val);
							$ret .= "</$key>";
						}
					}
					else {
						$ret .= $this->_getXmlValue($value);
					}
					$ret .= "</$field>";
				}
				
				$ret .= '</record>';
			}
			$ret .= '</data>';
		}
		
		$ret .= '</response>';
        		
		return $ret;
	}
	
	private function _getXmlValue($value, $fieldType = null) {
		if(is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		
		return "<![CDATA[$value]]>";
	}
	
}