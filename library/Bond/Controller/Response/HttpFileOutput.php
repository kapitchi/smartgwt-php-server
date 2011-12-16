<?php

class Bond_Controller_Response_HttpFileOutput extends Zend_Controller_Response_Http {
	private $_filePath;
	private $_resource;
	
	private $_fileName;
	private $_fileMime = 'application/octet-stream';
	
	public function setFileName($data) {
		$this->_fileName = $data;
	}
	
	public function getFileName() {
		return $this->_fileName;
	}
	
	public function setFilePath($data) {
		$this->_filePath = $data;
	}
	
	public function setFileMime($data) {
		$this->_fileMime = $data;
	}
	
	public function getFileMime() {
		return $this->_fileMime;
	}
	
	public function setResource($data) {
		$this->_resource = $data;
	}
	
	public function getResource() {
		if(is_resource($this->_resource)) {
			return $this->_resource;
		}
		
		if($this->_filePath && Zend_Loader::isReadable($this->_filePath)) {
			$this->_resource = @fopen($this->_filePath, "rb");
			if($this->_resource !== false) {
				return $this->_resource;
			}
		}
		
		throw new Zend_Exception("Invalid resource");
	}
	
	public function getFileSize() {
		if($this->_filePath) {
			$stat = @stat($this->_filePath);
			if($stat !== false) {
				return $stat['size'];
			}
		}
		
		return false;
	}
	
	public function sendHeaders() {
		$overwrite = true;
		
		$filename = $this->getFileName();
    	$size = $this->getFileSize();
    	$resource = $this->getResource();
    	
    	$mime = $this->getFileMime();
    	
    	$this->setHeader('Content-Description', 'File Transfer', $overwrite);
    	$this->setHeader('Content-Type', $mime, $overwrite);
    	
    	$this->setHeader('Content-Disposition', 'attachment; filename=' . $filename, $overwrite);
    	$this->setHeader('Content-Transfer-Encoding', 'binary', $overwrite);
    	$this->setHeader('Expires', '0', $overwrite);
    	$this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', $overwrite);
    	$this->setHeader('Pragma', 'public', $overwrite);
    	
    	if($size !== false) {
    		$this->setHeader('Content-Length', $size, $overwrite);
    	}
    	
    	parent::sendHeaders();
	}
	
	
	/**
     * Echo the body segments
     *
     * @return void
     */
    public function outputBody()
    {
    	$resource = $this->getResource();
    	
    	rewind($resource);
    	fpassthru($resource);
    	fclose($resource);
    	
    	//@XXX do we have to exit here?
    	exit;
    }
}