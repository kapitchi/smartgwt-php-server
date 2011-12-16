<?php

class Bond_SmartGwt_Request {
	const METADATA_PREFIX_DEFAULT = '_';
	const TYPE_FETCH = 'fetch';
	const TYPE_ADD = 'add';
	const TYPE_UPDATE= 'update';
	const TYPE_REMOVE = 'remove';
	const TYPE_CUSTOM = 'custom';
	
	const TEXT_MATCH_STARTS_WITH = 'starts_with';
	const TEXT_MATCH_SUBSTRING = 'substring';
	const TEXT_MATCH_EXACT = 'exact';
	
	private $_metaDataPrefix = self::METADATA_PREFIX_DEFAULT;
	private $_metaData = array();
	private $_data = array();
	private $_rawData = array();
	private $_criteria = array();
	
	private $_exportAs;
	private $_exportFilename;
	
	public function fromArray(array $params) {
	    
	    if(isset($params['_constructor'])) {
	        $const = $params['_constructor'];
	        unset($params['_constructor']);
	        
	        if($const == 'AdvancedCriteria') {
	            $oper = $params['operator'];
	            unset($params['operator']);
	            $criteria = $params['criteria'];
	            unset($params['criteria']);

	            $criArr = Zend_Json::decode($criteria);
	            $this->addCriteria($criArr);
	        }
	        else {
	            throw new Exception("Unknown constructor '$const'");
	        }
	        
	    }
	    
		foreach($params as $param => $value) {
		    //TODO ignore for now
		    if(strpos($param, 'isc_') === 0) {
		        continue;
		    }
			if(strpos($param, $this->getMetaDataPrefix()) === 0) {
				$this->setMetaParam(substr($param, strlen($this->getMetaDataPrefix())), $value);
			}
			else {
				$this->setRawDataField($param, $value);
			}
		}
		
		//@TODO check for valid request?
	}
	
	public function fromXml($xml) {
		throw new Zend_Exception("N/I");
	}
	
	public function fromJson($xml) {
		throw new Zend_Exception("N/I");
	}
	
	public function addCriteria($crit) {
	    $fieldName = $crit['fieldName'];
	    $oper = $crit['operator'];
	    $value = $crit['value'];
	    
	    $this->_criteria[] = array('fieldName' => $fieldName, 'operator' => $oper, 'value' => $value);
	}
	
    public function setCriteria($crits) {
	    $this->_criteria = $crits;
	}
	
	public function getCriteria() {
	    return $this->_criteria;
	}
	
	public function isValid() {
		$dsName = $this->getDataSource();
		return !empty($dsName);
	}
	
	public function setExportAs($data) {
		$this->_exportAs = $data;
	}
	
	public function getExportAs() {
		return $this->_exportAs;
	}
	
	public function setExportFilename($data) {
		$this->_exportFilename = $data;
	}
	
	public function getExportFilename() {
		return $this->_exportFilename;
	}
	
	public function setMetaDataPrefix($prefix) {
		$this->_metaDataPrefix = $prefix;
	}
	
	public function getMetaDataPrefix() {
		return $this->_metaDataPrefix;
	}
	
	public function getMetaParam($param) {
		if(array_key_exists($param, $this->_metaData)) {
			return $this->_metaData[$param];
		}
		
		return null;
	}
	
	public function setMetaParam($param, $val) {
		return $this->_metaData[$param] = $val;
	}
	
	public function getDataSource() {
		return $this->getMetaParam('dataSource');
	}
	
	public function setDataSource($ds) {
		$this->setMetaParam('dataSource', $ds);
	}
	
	public function getOperationType() {
		return $this->getMetaParam('operationType');
	}
	
    public function getTextMatchStyle() {
		return $this->getMetaParam('textMatchStyle');
	}
	
	public function setOperationType($type) {
		//@TODO check for options
		
		$this->setMetaParam('operationType', $type);
	}
	
    public function setOperationId($type) {
		//@TODO check for options
		
		$this->setMetaParam('operationId', $type);
	}
	
	public function getOperationId() {
		return $this->getMetaParam('operationId');
	}
	
	public function getStartRow() {
		return $this->getMetaParam('startRow');
	}
	
	public function setStartRow($val) {
		$this->setMetaParam('startRow', $val);
	}
	
	public function getEndRow() {
		return $this->getMetaParam('endRow');
	}
	
	public function getOrderBy() {
		return $this->getMetaParam('orderBy');
	}
	
	public function setOrderBy($val) {
		$this->setMetaParam('orderBy', $val);
	}
	
	public function setEndRow($val) {
		$this->setMetaParam('endRow', $val);
	}
	
	public function getOldValues() {
		$json = $this->getMetaParam('oldValues');
		$vals = Zend_Json::decode($json);
		
		return $vals;
	}
	
	public function getOldData() {
		$vals = $this->_filterDataFromArray($this->getOldValues());
		return $vals;
	}
	
	public function setRawDataField($data, $origValue) {
		//$value = $this->_sanitazeValue($value);
		
//		try {
			//$value = Zend_Json::decode($origValue);
//		} catch(Zend_Json_Exception $e) {
			//@TODO we need some hints which one to translate and which not - from datasource?
//			$value = $origValue;
//		}
		
		$this->_rawData[$data] = $origValue;
	}
	
	/*private function _sanitazeValue($value) {
		switch($value) {
			case 'null':
				$value = null;
				break;
			case 'true':
				$value = true;
				break;
			case 'false':
				$value = false;
				break;
		}
		
		return $value;
	}*/
	
	public function getRawData() {
		return $this->_rawData;
	}
	
	private function _filterMetaDataFromArray(array $params) {
		$data = array();
		foreach($params as $param => $value) {
			if(strpos($param, $this->getMetaDataPrefix()) === 0) {
				$data[$param] = $value;
			}
		}
		
		return $data;
	}
	
	private function _filterDataFromArray(array $params) {
		$data = array();
		foreach($params as $param => $value) {
			if(strpos($param, $this->getMetaDataPrefix()) !== 0) {
				$data[$param] = $value;
			}
		}
		
		return $data;
	}
	
}