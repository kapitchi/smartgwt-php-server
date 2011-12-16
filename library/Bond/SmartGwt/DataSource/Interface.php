<?php

interface Bond_SmartGwt_DataSource_Interface {
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function handle(Bond_SmartGwt_Request $request);
	
	/**
     * @param Bond_SmartGwt_Request $request
     * @return Bond_SmartGwt_Response $response
     */
    public function custom(Bond_SmartGwt_Request $request);
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function fetch(Bond_SmartGwt_Request $request);
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function add(Bond_SmartGwt_Request $request);
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function update(Bond_SmartGwt_Request $request);
	
	/**
	 * @param Bond_SmartGwt_Request $request
	 * @return Bond_SmartGwt_Response $response
	 */
	public function remove(Bond_SmartGwt_Request $request);
}