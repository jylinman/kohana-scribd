<?php defined('SYSPATH') or die('No direct access allowed.');

class Scribd {

	protected $_config = NULL;

	/**
	 * If user is logged in, add session key to perform actions
	 */
	protected $_session_key = NULL;

	public function __construct($config = array())
	{
		$this->_config = $config;
	}

	public static $instance = NULL;

	public static function instance($config = NULL)
	{
		if (isset(Scribd::$instance))
		{
			// Return the current group if initiated already
			return Scribd::$instance;
		}

		$config = Kohana::$config->load('scribd');

		// Create a new scribd instance
		Scribd::$instance = new Scribd($config);

		// Return the instance
		return Scribd::$instance;
	}

	/**
	 * Makes an API call with requested data
	 */
	public function call($action, $params = array())
	{
		// Add API key to each request
		$params['api_key'] = $this->_config->api_key;

		// Append session key if logged in
		if ($this->_session_key !== NULL) $params['session_key'] = $this->_session_key;

		if (! function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		$request = curl_init($this->_config->api_url . URL::query($params));
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($request);
		curl_close($request);

		if (! $response)
			throw new Kohana_Exception('Error connecting to API gateway [Scribd]');

		return $response;
	}

}