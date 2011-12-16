<?php

class Bond_SmartGwt_DataSource_DbTable extends Bond_SmartGwt_DataSource_Abstract {
	
	protected $_dbTable = null;
	protected $_parentDataSources = null;
	protected $_references = null;
	
	private $_tableMeta = null;
	
	public function __construct($dbTable = null) {
		if($dbTable !== null) {
			$this->setDbTable($dbTable);
		}
		
		parent::__construct();
	}
	
	/**
	 * @return Zend_Db_Table_Abstract
	 */
	public function getDbTable() {
		if($this->_dbTable instanceof Zend_Db_Table_Abstract) {
			return $this->_dbTable;
		}
		
		//@TODO do we want to initialize dbtable this way???
		if(is_string($this->_dbTable)) {
			$table = new $this->_dbTable;
			$this->setDbTable($table);
			return $table;
		}
		
		//@TODO exception instead?
		return $this->_dbTable;
	}
	
	public function setDbTable(Zend_Db_Table_Abstract $table) {
		$this->_dbTable = $table;
	}
	
	/**
	 * Applies where conditions, but no limit/order by
	 * 
	 * @param Bond_SmartGwt_Request $request
	 * @return Zend_Db_Select
	 */
	protected function _getFetchSelect(Bond_SmartGwt_Request $request) {
	    $dbTable = $this->getDbTable();
		$select = $dbTable->select();
		
		$this->_selectFetchWhere($select, $request);
		$this->_selectFetchLimit($select, $request);
		$this->_selectFetchOrder($select, $request);
		
		return $select;
	}
	
	protected function _selectFetchLimit(Zend_Db_Select $select, Bond_SmartGwt_Request $request) {
	    $start = $request->getStartRow();
		$end = $request->getEndRow();
		
		$select->limit($end - $start, $start);
		
		return $select;
	}
	
    protected function _selectFetchOrder(Zend_Db_Select $select, Bond_SmartGwt_Request $request) {
	    $orderBy = $request->getOrderBy();
        if($orderBy) {
			$select->order($orderBy);
		}
		
		return $select;
	}
	
	protected function _selectFetchWhere(Zend_Db_Select $select, Bond_SmartGwt_Request $request) {
	    
	    //advancedcriteria
    	if(count($request->getCriteria()) > 0) {
    	    $crits = $request->getCriteria();
    	    foreach($crits as $crit) {
    	        $field = $crit['fieldName'];
    	        $op = $crit['operator'];
    	        $value = $crit['value'];
    	        
    	        if(!$this->_isTableField($field)) {
		            continue;
		        }
    	        
    	        switch($op) {
    	            case 'iContains':
    	                $select->where("$field LIKE ?", "%$value%");
    	                break;
    	            default:
    	                throw new Exception("Advanced criteria operator '$op' not implemented");
    	        }
    	        
    	    }
    	}
    	else {
    		//basic criteria
    		$data = $this->_getData($request);
    		if($data) {
    		    $style = $request->getTextMatchStyle();
    		    foreach($data as $field => $value) {
    		        if(!$this->_isTableField($field)) {
    		            continue;
    		        }
    		        
    		        //TODO check for field type here! 
    		        switch($style) {
    		            case Bond_SmartGwt_Request::TEXT_MATCH_SUBSTRING:
    		                $select->where("$field LIKE ?", "%$value%");
    		                break;
    		            case Bond_SmartGwt_Request::TEXT_MATCH_STARTS_WITH:
    		                $select->where("$field LIKE ?", "$value%");
    		                break;
    		            case Bond_SmartGwt_Request::TEXT_MATCH_EXACT:
    		            default:
    		                $select->where("$field = ?", $value);
    		        }
    				
    			}
    		}
		}
		
		return $select;
	}
	
	protected function _getTotalRows(Bond_SmartGwt_Request $request) {
	    $dbTable = $this->getDbTable();
		$select = $dbTable->select();
		
	    $select->from($dbTable, array('count(*) as totalRows'));
	    $this->_selectFetchWhere($select, $request);
	    
	    $row = $dbTable->fetchRow($select);
	    if($row === null) {
	        throw new Exception("Could not get totalRows");
	    }
	    return (int)$row->totalRows;
	}
	
	/**
	 * @param  Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response
	 */
	public function fetch(Bond_SmartGwt_Request $request) {
		$response = $this->_createResponse($request);
		
		$select = $this->_getFetchSelect($request);
		
		$dbTable = $this->getDbTable();
		
		//$this->_getTotalRows($select);
		$rowSet = $dbTable->fetchAll($select);

		$totalRows = $this->_getTotalRows($request);
		$response->setTotalRows($totalRows);
		
		$data = array();
		
		foreach($rowSet as $row) {
			$rec = $row->toArray();
			
			//parent datasources
			/*foreach($this->getReferences() as $ref) {
    			$refMeta = $dbTable->getReference($ref);
    			$parent = $row->findParentRow($ref);
    			if($parent !== null) {
	    			//@TODO checks!
	    			$recColumn = $refMeta['columns'][0];
	    			$rec[$recColumn] = $this->_sanitazeResponseValues($parent->toArray());
    			}
			}*/
			//END parent datasources
			$data[] = $this->_sanitazeResponseValues($rec, $row);
		}
		$response->setData($data);
		
		return $response;
	}
	
	public function getReferences() {
		$refs = $this->_references;
		if(empty($refs)) {
			$refs = array();
		}
		else {
			$refs = (array)$this->_references;
		}
		
		return $refs;
	}
	
	/*public function getParentDataSources() {
		if(empty($this->_parentDataSources)) {
			return array();
		}
		
		foreach($this->_parentDataSources as $datasource) {
			if(is_string($datasource)) {
				$ds = new $datasource;
			}
			else {
				throw new Zend_Exception("Can't recognize this type of datastore");
			}
			
			$table = $ds->getDbTable();
			$refMeta = $table->getReference($ref);
		}
	}*/
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function add(Bond_SmartGwt_Request $request) {
		$response = $this->_createResponse($request);
		
		$newVals = $this->_getData($request);
		$dbTable = $this->getDbTable();
		
		$errors = $this->_validateRow($newVals);
		if(!empty($errors)) {
			$response->setValidationErrors($errors);
			return $response;
		}
		
		/*var_dump($dbTable->info(Zend_Db_Table_Abstract::COLS));
		exit;*/
		
		$row = $dbTable->createRow($newVals);
		
		$dbTable->getAdapter()->beginTransaction();
		try {
			$row->save();
			
			$this->_postSave($row, $newVals);
			
			$dbTable->getAdapter()->commit();
		} catch(Exception $e) {
			$dbTable->getAdapter()->rollBack();
			throw $e;
		}
		
		$data = $this->_sanitazeResponseValues($row->toArray(), $row);
		$response->setData(array($data));
		return $response;
	}
	
	public function update(Bond_SmartGwt_Request $request) {
		$response = $this->_createResponse($request);
		
		$dbTable = $this->getDbTable();
		
		$newVals = $this->_getData($request);
		$row = $this->_findRowByPrimary($newVals);
		
		$errors = $this->_validateRow($newVals);
		if(!empty($errors)) {
			$response->setValidationErrors($errors);
			return $response;
		}
		
		$dbTable->getAdapter()->beginTransaction();
		try {
		    $this->_preUpdate($row, $newVals);
		    
    		foreach($row->toArray() as $field => $val) {
    		    if(array_key_exists($field, $newVals) && $val != $newVals[$field]) {
    			    $row->__set($field, $newVals[$field]);
    			}
    		}
		    
			$row->save();
			
			$this->_postSave($row, $newVals);
			
			$dbTable->getAdapter()->commit();
		} catch(Exception $e) {
			$dbTable->getAdapter()->rollBack();
			throw $e;
		}
		
		//$row = $dbTable->find($row->id);
		//var_dump($row);
		//exit;
		
		$data = $this->_sanitazeResponseValues($row->toArray(), $row);
		$response->setData(array($data));
		
		return $response;
	}
	
	protected function _postSave(Zend_Db_Table_Row_Abstract $row, $newData) {
	    
	}
	
    protected function _preUpdate(Zend_Db_Table_Row_Abstract $origRow, &$newData) {
	    
	}
	
	protected function _saveReferencedRow($field, $origValue, array $values) {
	    throw new Zend_Exception("N/I - remove?");
		//xxxxxxxxxxxxxxxxxxx ====== yyyyyyyyyyyyyyyyyyyy
		var_dump($field);
		var_dump($origValue);
		var_dump($values);
		exit;
	}
	
	protected function _isComplexField($field) {
	    throw new Zend_Exception("N/I - remove?");
	    
		$dbTable = $this->getDbTable();
		//parent datasources
		$refs = $this->getReferences();
		foreach($refs as $ref) {
    		$refMeta = $dbTable->getReference($ref);
    		//$parent = $row->findParentRow($ref);
    		$recColumn = $refMeta['columns'][0];
    		if($recColumn == $field) {
				return $ref;
    		}
		}
		
		return false;
	}
	
	protected function _preRemove(Zend_Db_Table_Row_Abstract $row) {
	    $dbTable = $this->getDbTable();
	    foreach($dbTable->getDependentTables() as $tableName) {
	        $table = $this->_getTableFromString($tableName);
	        $ref = $table->getReference(get_class($dbTable));
	        
	        if(isset($ref['onDelete']) && $ref['onDelete'] == Zend_Db_Table::RESTRICT) {
	            $depRows = $row->findDependentRowset($tableName);
	            if(count($depRows) > 0) {
	                //TODO message?
	                $pos = strrpos($tableName, '_');
	                
	                $entityName = $tableName;
	                if($pos !== false) {
	                    $entityName = substr($tableName, $pos + 1);
	                }
	                
	                $errors[] = $entityName;	                
	            }
	        }
	    }
	    
	    if(!empty($errors)) {
	        $entityNames = implode('/', $errors);
	        throw new Zend_Exception("You cannot delete this entry this is associated to another record ($entityNames)");
	    }
	}
	
	/**
	 * @param string $tableName
	 * @return Zend_Db_Table_Abstract
	 */
    protected function _getTableFromString($tableName)
    {
        // assume the tableName is the class name
        if (!class_exists($tableName)) {
            try {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($tableName);
            } catch (Zend_Exception $e) {
                require_once 'Zend/Db/Table/Row/Exception.php';
                throw new Zend_Db_Table_Row_Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        $options = array();

        $table = $this->getDbTable();
        if ($table) {
            $options['db'] = $table->getAdapter();
        }

        return new $tableName($options);
    }
	
	public function remove(Bond_SmartGwt_Request $request) {
		$response = $this->_createResponse($request);
		
		$dbTable = $this->getDbTable();
		$row = $this->_findRowByPrimary($this->_getData($request));
		
		$data = $this->_sanitazeResponseValues($row->toArray(), $row);
		
		$dbTable->getAdapter()->beginTransaction();
		try {
		    $this->_preRemove($row);
		    
			$row->delete();
			$dbTable->getAdapter()->commit();
		} catch(Exception $e) {
			$dbTable->getAdapter()->rollBack();
			throw $e;
		}
		
		$response->setData(array($data));
		return $response;
	}
	
	protected function _getTableMeta() {
		if($this->_tableMeta === null) {
			$table = $this->getDbTable();
			$this->_tableMeta = $table->info(Zend_Db_Table::METADATA);
		}
		
		return $this->_tableMeta;
	}
	
	protected function _sanitazeResponseValue($field, $value) {
		//@TODO do we really want to ignore if field does not exist? 
		if($this->_isTableField($field)) {
			//boolean
			if($this->_getTableFieldMeta($field, 'DATA_TYPE') == 'tinyint') {
				return (bool)$value;
			}
		}
		
		return parent::_sanitazeResponseValue($field, $value);
	}
	
    protected function _sanitazeRequestValue($field, $value) {
		$value = parent::_sanitazeRequestValue($field, $value);
		if(empty($value)) {
            if($this->_isTableField($field)) {
    		    if($this->_getTableFieldMeta($field, 'NULLABLE')) {
    		        $value = null;
    		    }
		    }
		    
		}
		
		return $value;
	}
	
	protected function _isTableField($field) {
	    $info = $this->_getTableMeta();
	    if(isset($info[$field]) ) {
	        return true;
	    }
	    
	    return false;
	}
	
	protected function _getDbTableName() {
	    $tableName = $this->getDbTable()->info(Zend_Db_Table::NAME);
	    return $tableName;
	}
	
	protected function _getTableFieldMeta($field, $metaField = null) {
	    $tableName = $this->_getDbTableName();
	    
	    if(!$this->_isTableField($field)) {
	        throw new Exception("Field '$field' does not exist in the table '$tableName'");
	    }
	    
	    $info = $this->_getTableMeta();
        if(!isset($info[$field])) {
            throw new Exception("No meta info about field '$field' in the table '$tableName'");
        }
        
	    $meta = $info[$field];
	    
	    if($metaField === null) {
	        return $meta;
	    }
	    
	    if(!isset($meta[$metaField])) {
	        throw new Exception("No meta '$metaField' about field '$field' in the table '$tableName'");
	    }
	    
	    return $meta[$metaField];
	}
	
	/**
	 * @param array $oldVals
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _findRowByPrimary(array $vals) {
		$dbTable = $this->getDbTable();
		
		$primaryKey = $dbTable->info('primary');
		
		//@TODO is this ok? need testing.
		$keys = array();
		foreach($primaryKey as $key) {
		    if(empty($vals[$key])) {
		        throw new Exception("Value for key field '$key' not sent");
		    }
			$keys[] = $vals[$key];
		}
		$res = call_user_func_array(array($dbTable, 'find'), $keys);
		if(count($res) != 1) {
			throw new Zend_Exception("No unique record with keys " . print_r($keys, true));
		}
		
		$row = $res->current();
		
		return $row;
	}
}