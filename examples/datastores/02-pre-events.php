<?php

class Api_Model_CmsPage extends Bond_SmartGwt_DataSource_DbTable {
	
	protected $_dbTable = 'Ilm_Model_DbTable_CmsPage';
	
	
    protected function _preUpdate(Zend_Db_Table_Row_Abstract $origRow, &$newData) {
	    if($origRow->handle == 'home') {
	        if($newData['handle'] != 'home') {
	            throw new Exception("This page is system locked - you can not change the handle");
	        }
	    }
	}
	
	protected function _preRemove(Zend_Db_Table_Row_Abstract $row) {
	    if($row->handle == 'home') {
	        throw new Exception("This page is system locked - you can not delete this page");
	    }
	    return parent::_preRemove($row);
	}
	
}