<?php

class Bond_SmartGwt_Server {
	
	private $_dataSources = array();
	
	/**
	 * @param unknown_type $request
	 * @return Zend_Controller_Response_Http
	 */
	public function handle($request = null) {
		try {
			if($request === null) {
				$request = new Bond_SmartGwt_Request_Http();
			}
			
			if(!$request->isValid()) {
				throw new Zend_Exception("Invalid SmartGwt request");
			}
			
			$dsName = $request->getDataSource();
			$type = $request->getOperationType();
			
			$ds = $this->getDataSource($dsName);
			$response = $ds->handle($request);
			
			//@TODO do it smarter
			$exportAs = $request->getExportAs();
			$isFileDownload = !empty($exportAs);
			if($isFileDownload) {
				$httpResponse = new Bond_Controller_Response_HttpFileOutput();
				$httpResponse->setFileName($request->getExportFilename());
				switch($exportAs) {
					case 'csv':
						$httpResponse->setFileMime('text/csv');
						break;
					default:
						throw new Zend_Exception("Invalid export format defined");
				}
				
				//$httpResponse->setFilePath(__FILE__);
				
				$res = fopen('php://memory', "rw");
				
				$data = $response->getData();
				
				//header
				$first = current($data);
				fputcsv($res, array_keys($first));
				
				//rows
				foreach($data as $row) {
					fputcsv($res, $row);
				}
				
				$httpResponse->setResource($res);
			}
			else {
				$httpResponse = new Zend_Controller_Response_Http();
				$httpResponse->setHeader('Content-Type', 'text/xml', true);
				$httpResponse->appendBody($response->toXml());
			}
		}
		catch(Exception $e) {
		    $this->getLog()->err($e);
		    
		    $type = $request->getOperationType();
		    $opId = $request->getOperationId();
		    
		    $msg = $request->getDataSource();
		    $msg .= " [type=$type";
		    if(!empty($opId)) {
		        $msg .= ",opId=$opId";
		    }
		    $msg .= "] - ";
			$msg .= $e->getMessage();
    		
			$response = new Bond_SmartGwt_Response();
			$response->setErrors($msg, Bond_SmartGwt_Response::STATUS_FAILURE);
    		
    		$httpResponse = new Zend_Controller_Response_Http();
			$httpResponse->setHeader('Content-Type', 'text/xml', true);
			$httpResponse->appendBody($response->toXml());
		}
		
		return $httpResponse;
	}
	
	public function setLog(Zend_Log $log) {
	    $this->_log = $log;
	}
	
	public function getLog() {
	    if (!$this->_log instanceOf Zend_Log ) {
            require_once 'Zend/Log.php';
            require_once 'Zend/Log/Writer/Null.php';
            $writer = new Zend_Log_Writer_Null();
            $this->setLog(new Zend_Log($writer));
        }
        
        return $this->_log;
	}
	
	public function addDataSource(Bond_SmartGwt_DataSource_Interface $dataSource, $name) {
		//$name =
		//@TODO check if exists if so - exception
		$this->_dataSources[$name] = $dataSource;
	}
	
	/**
	 * @param string $name
	 * @return Bond_SmartGwt_DataSource_Interface
	 */
	public function getDataSource($name) {
		if(!array_key_exists($name, $this->_dataSources)) {
			throw new Zend_Exception("No existing datasource '$name'");
		}
		
		return $this->_dataSources[$name];
	}
	
}