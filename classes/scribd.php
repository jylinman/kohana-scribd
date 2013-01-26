<?php defined('SYSPATH') or die('No direct access allowed.');

class Scribd {

	protected $_error = NULL;
	protected $_config = NULL;
	protected $_session_key = NULL;
	protected $_user_id = NULL;

	public function __construct()
	{
		$this->_config = Kohana::$config->load('scribd');
	}

	public static $instance = NULL;

	public static function instance()
	{
		if (isset(Scribd::$instance))
		{
			// Return the current group if initiated already
			return Scribd::$instance;
		}

		// Create a new scribd instance
		Scribd::$instance = new Scribd();

		// Return the instance
		return Scribd::$instance;
	}


	/**
	 * Upload a document from a file
	 *
	 * @param string $file : relative path to file
	 * @param string $doc_type : PDF, DOC, TXT, PPT, etc.
	 * @param string $access : public or private. Default is Public.
	 * @param int $rev_id : id of file to modify
	 * @return array containing doc_id, access_key, and secret_password if nessesary.
	 */
	public function upload($file, $doc_type = NULL, $access = NULL, $rev_id = NULL)
	{
		$params['doc_type'] = $doc_type;
		$params['access'] = $access;
		$params['file'] = "@".$file;

		$result = $this->post('docs.upload', $params);
		return $result;
	}


	/**
	 * Upload a document from a Url
	 *
	 * @param string $url : absolute URL of file 
	 * @param string $doc_type : PDF, DOC, TXT, PPT, etc.
	 * @param string $access : public or private. Default is Public.
	 * @return array containing doc_id, access_key, and secret_password if nessesary.
	 */
	public function upload_from_url($url, $doc_type = NULL, $access = NULL, $rev_id = NULL)
	{
		$params['url'] = $url;
		$params['access'] = $access;
		$params['rev_id'] = $rev_id;
		$params['doc_type'] = $doc_type;

		$data_array = $this->get('docs.uploadFromUrl', $params);
		return $data_array;
	}

	/**
	 * Get a list of the current users files
	 *
	 * @return array containing doc_id, title, description, access_key, and conversion_status for all documents
	 */
	public function get_list()
	{
		$result = $this->get('docs.getList');
		return $result['resultset'];
	}

	/**
	 * Get the current conversion status of a document
	 *
	 * @param int $doc_id : document id
	 * @return string containing DISPLAYABLE", "DONE", "ERROR", or "PROCESSING" for the current document.
	 */
	public function get_conversion_status($doc_id)
	{
		$params['doc_id'] = $doc_id;

		$result = $this->get('docs.getConversionStatus', $params);
		return $result['conversion_status'];
	}

	/**
	 * Get settings of a document
	 *
	 * @return array containing doc_id, title , description , access, tags, show_ads, license, access_key, secret_password
	 */
	public function get_settings($doc_id){
		$params['doc_id'] = $doc_id;

		$result = $this->get('docs.getSettings', $params);
		return $result;
	}

	/**
	 * Change settings of a document
	 *
	 * @param array $doc_ids : document id
	 * @param string $title : title of document
	 * @param string $description : description of document
	 * @param string $access : private, or public
	 * @param string $license : "by", "by-nc", "by-nc-nd", "by-nc-sa", "by-nd", "by-sa", "c" or "pd"
	 * @param string $access : private, or public
	 * @param array $show_ads : default, true, or false
	 * @param array $tags : list of tags
	 * @return string containing DISPLAYABLE", "DONE", "ERROR", or "PROCESSING" for the current document.
	 */
	public function change_settings($doc_ids, $title = NULL, $description = NULL, $access = NULL, $license = NULL, $parental_advisory = NULL, $show_ads = NULL, $tags = NULL)
	{
		$params['doc_ids'] = $doc_ids;
		$params['title'] = $title;
		$params['description'] = $description;
		$params['access'] = $access;
		$params['license'] = $license;
		$params['show_ads'] = $show_ads;
		$params['tags'] = $tags;

		$result = $this->get('docs.changeSettings', $params);
		return $result;
	}

	/**
	 * Delete a document
	 *
	 * @param int $doc_id : document id
	 * @return 1 on success;
	 */
	public function delete($doc_id)
	{
		$params['doc_id'] = $doc_id;

		$result = $this->get('docs.delete', $params);
		return $result;
	}

	/**
	 * Search the Scribd database
	 * @param string $query : search query
	 * @param int $num_results : number of results to return (10 default, 1000 max)
	 * @param int $num_start : number to start from
	 * @param string $scope : scope of search, "all" or "user"
	 * @return array of results, each of which contain doc_id, secret password, access_key, title, and description
	 */
	public function search($query, $num_results = NULL, $num_start = NULL, $scope = NULL)
	{
		$params['query'] = $query;
		$params['num_results'] = $num_results;
		$params['num_start'] = $num_start;
		$params['scope'] = $scope;

		$result = $this->get('docs.search', $params);

		return $result['result_set'];
	}

	/**
	 * Login as a user
	 * @param string $username : username of user to log in
	 * @param string $password : password of user to log in
	 * @return array containing session_key, name, username, and user_id of the user;
	 */
	public function login($username, $password)
	{
		$params['username'] = $username;
		$params['password'] = $password;

		$result = $this->get('user.login', $params);
		$this->session_key = $result['session_key'];
		return $result;
	}

	/**
	 * Sign up a new user
	 * @param string $username : username of user to create
	 * @param string $password : password of user to create
	 * @param string $email : email address of user
	 * @param string $name : name of user
	 * @return array containing session_key, name, username, and user_id of the user;
	 */
	public function signup($username, $password, $email, $name = NULL)
	{
		$params['username'] = $username;
		$params['password'] = $password;
		$params['name'] = $name;
		$params['email'] = $email;

		$result = $this->get('user.signup', $params);
		return $result;
	}

	/**
	 * Sends a get request to the API
	 */
	public function get($action, $params = array())
	{
		$url = $this->_config->api_url;

		// Append session key if logged in
		if ($this->_session_key !== NULL) $params['session_key'] = $this->_session_key;
		if ($this->_user_id !== NULL) $params['my_user_id'] = $this->_user_id;

		if (! function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		// Add necessary parameters
		$params['method'] = $action;
		$params['api_key'] = $this->_config->api_key;

		$this->_generate_sig($params);

       	// Lets execute the request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . URL::query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$xml = curl_exec( $ch );
		$result = simplexml_load_string($xml); 
		curl_close($ch);

		if ($result['stat'] == 'fail')
		{
			$this->_error = array(
				'code' => (string) $result->error->attributes()->code,
				'message' => (string) $result->error->attributes()->message,
			);

			return FALSE;
		}

		if ($result['stat'] == "ok") return $result;
	}

	/**
	 * Makes an API post with requested data
	 */
	public function post($action, $params = array())
	{
		$request_url = $this->_config->api_url . URL::query(array(
			'method' => $action,
			'api_key' => $this->_config->api_key
		));

		// Append session key if logged in
		if ($this->_session_key !== NULL) $params['session_key'] = $this->_session_key;
		if ($this->_user_id !== NULL) $params['my_user_id'] = $this->_user_id;

		if (! function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		$params['api_sig'] = $this->_generate_sig($params);

       	// Lets execute the request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$xml = curl_exec( $ch );



		var_dump($xml, $ch);exit;




		// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^



		$result = simplexml_load_string($xml); 
		curl_close($ch);

		if ($result['stat'] == 'fail')
		{
			// This is ineffecient.
			$errors = (array) $result;
			$errors = (array) $errors;
			$errors = (array) $errors['error'];
			$errors = $errors['@attributes'];
			$this->_error = $errors['code'];

			throw new Exception($errors['message'], $errors['code']);

			return FALSE;
		}

		if ($result['stat'] == "ok")
		{
			$result = self::xml_to_array($result);

			return $result;
		}

		if (! $result)
			throw new Kohana_Exception('Error connecting to API gateway [Scribd]');

		return $response;
	}

	/**
	 * Generate public signature with parameters
	 */
	protected function _generate_sig(&$params)
	{
		// Signature cannot be in the keys
		if (isset($params['api_sig'])) unset($params['api_sig']);

		ksort($params);

		$str = $this->_config['api_secret'];
		foreach ($params as $k => $v) 
		{
			$str .= $k . $v;
		}

		$params['api_sig'] = md5($str);
	}

	public function __get($key)
	{
		if ($key == 'error') return $this->_error;

		return parent::__get($key);
	}

}




































