<?php

class Api_V1Controller extends Zend_Controller_Action {

    private $_log;

    public function handleErrors($num, $err, $file, $line) {
        require_once 'Bond/SmartGwt/Response.php';
        require_once 'Zend/Controller/Response/Http.php';

        $error = "PHP ERROR: $err [$file, line $line]";
        $this->_getLog()->err($error);

        $response = new Bond_SmartGwt_Response();
        $response->setErrors($error, Bond_SmartGwt_Response::STATUS_FAILURE);

        $httpResponse = new Zend_Controller_Response_Http();
        $httpResponse->setHeader('Content-Type', 'text/xml', true);
        $httpResponse->appendBody($response->toXml());
        $httpResponse->sendResponse();
        exit;
    }

    private function _getLog() {
        if (!Zend_Registry::isRegistered('log/api')) {
            require_once 'Zend/Log.php';
            require_once 'Zend/Log/Writer/Null.php';
            $writer = new Zend_Log_Writer_Null();
            $this->_log = new Zend_Log($writer);
        } else {
            $this->_log = Zend_Registry::get('log/api');
        }

        return $this->_log;
    }

    public function init() {
        set_error_handler(array($this, 'handleErrors'));

        $this->_helper->viewRenderer->setNoRender();

        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout) {
            $layout->disableLayout();
        }

        try {
            $this->_helper->user()->adminCheck();
        } catch (Zend_Auth_Exception $e) {
            $error = 'You have been logged out - please relogin on front-end';
            $response = new Bond_SmartGwt_Response();
            $response->setErrors($error, Bond_SmartGwt_Response::STATUS_LOGIN_REQUIRED);

            $httpResponse = new Zend_Controller_Response_Http();
            $httpResponse->setHeader('Content-Type', 'text/xml', true);
            $httpResponse->appendBody($response->toXml());
            $httpResponse->sendResponse();
            exit;
        } catch (Exception $e) {
            $error = $e;
            $response = new Bond_SmartGwt_Response();
            $response->setErrors($error, Bond_SmartGwt_Response::STATUS_FAILURE);

            $httpResponse = new Zend_Controller_Response_Http();
            $httpResponse->setHeader('Content-Type', 'text/xml', true);
            $httpResponse->appendBody($response->toXml());
            $httpResponse->sendResponse();
            exit;
        }
    }

    public function indexAction() {
        $eventBand = new Bond_SmartGwt_DataSource_DbTable();
        $eventBand->setDbTable(new Ilm_Model_DbTable_EventBand());

        $cmsBlock = new Bond_SmartGwt_DataSource_DbTable();
        $cmsBlock->setDbTable(new Ilm_Model_DbTable_CmsBlock());

        $server = new Bond_SmartGwt_Server();
        $server->setLog($this->_getLog());

        $server->addDataSource(new Api_Model_Event(), 'EventDS');
        $server->addDataSource(new Api_Model_EventViewer(), 'EventViewerDS');
        $server->addDataSource(new Api_Model_Venue(), 'VenueDS');
        $server->addDataSource(new Api_Model_User(), 'UserDS');
        $server->addDataSource(new Api_Model_Age(), 'AgeDS');
        $server->addDataSource(new Api_Model_Country(), 'CountryDS');
        $server->addDataSource(new Api_Model_Page(), 'PageDS');
        $server->addDataSource(new Api_Model_Stream(), 'StreamDS');
        $server->addDataSource(new Api_Model_Band(), 'BandDS');
        $server->addDataSource(new Api_Model_Genre(), 'GenreDS');
        $server->addDataSource($eventBand, 'EventBandDS');
        $server->addDataSource(new Api_Model_Voucher(), 'VoucherDS');
        $server->addDataSource($cmsBlock, 'CmsBlockDS');
        $server->addDataSource(new Api_Model_CmsPage(), 'CmsPageDS');
        $server->addDataSource(new Api_Model_EventReview(), 'EventReviewDS');
        $server->addDataSource(new Api_Model_Sale(), 'SaleDS');
        $server->addDataSource(new Api_Model_File(), 'FileDS');
        $server->addDataSource(new Api_Model_System($this->getInvokeArg('bootstrap')), 'SystemDS');

        $httpResponse = $server->handle();
        $httpResponse->sendResponse();
        exit;
    }

    public function exportAction() {
        $request = new Bond_SmartGwt_Request_Http();
        $request->setExportAs('csv');
        $request->setExportFilename('export.csv');

        $server = new Bond_SmartGwt_Server();

        $server->addDataSource(new Api_Model_User(), 'UserDS');
        $server->addDataSource(new Api_Model_Sale(), 'SaleDS');

        $httpResponse = $server->handle($request);

        $httpResponse->sendResponse();
        exit;
    }

}
