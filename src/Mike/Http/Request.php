<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http;

use Mike\Exception;
use Mike\DependencyContainer\Injectable;


class Request extends Injectable {


	protected $_rawBody;

	/**
	 * @var \Mike\Utilities\Filter $_filter
	 */

	protected $_filter;

	protected $_putCache;

	protected $_httpMethodParameterOverride = false;

	protected $_strictHostCheck = false;

	/**
	 * Get $_REQUEST param
	 *
	 * @param string $name
	 * @param array|string $filters
	 * @param string $defaultValue
	 * @param bool $notAllowEmpty
	 *
	 * @return string|null
	 * @throws Exception
	 */

	public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false) {

		return $this->getHelper($_REQUEST, $name, $filters, $defaultValue, $notAllowEmpty);
	}

	/**
	 * Get $_POST param
	 *
	 * @param string $name
	 * @param array|string $filters
	 * @param string $defaultValue
	 * @param bool $notAllowEmpty
	 *
	 * @return string|null
	 * @throws Exception
	 */

	public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false) {

		return $this->getHelper($_POST, $name, $filters, $defaultValue, $notAllowEmpty);
	}

	/**
	 * Get param of PUT request
	 *
	 * @param string $name
	 * @param array|string $filters
	 * @param string $defaultValue
	 * @param bool $notAllowEmpty
	 *
	 * @return string|null
	 * @throws Exception
	 */

	public function getPut($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false) {

		$put = $this->_putCache;

		if(!is_array($put)) {

			$put = [];

			parse_str($this->getRawBody(), $put);

			$this->_putCache = $put;
		}

		return $this->getHelper($put, $name, $filters, $defaultValue, $notAllowEmpty);
	}

	/**
	 * Get $_GET param
	 *
	 * @param string $name
	 * @param array|string $filters
	 * @param string $defaultValue
	 * @param bool $notAllowEmpty
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false) {

		return $this->getHelper($_GET, $name, $filters, $defaultValue, $notAllowEmpty);

	}

	/**
	 * Helper for superglobals
	 *
	 * @param array $source
	 * @param string $name
	 * @param array|string $filters
	 * @param string $defaultValue
	 * @param bool $notAllowEmpty
	 *
	 * @return null|string|array
	 * @throws Exception
	 */

	private function getHelper(array $source, $name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false) {

		if(is_null($name))
			return $source;

		if(!isset($source[$name]))
			return $defaultValue;

		$value = $source[$name];

		if(!is_null($filters)) {

			$filter = $this->_filter;

			if(!is_object($filter)) {

				if(!is_object($this->_dependencyContainer))
					throw new Exception('Dependency Container is required to access \'filter\' service.');

				$this->_filter = $this->_dependencyContainer->get('filter');
			}

			$value = $filter->sanitize($value, $filters);
		}

		if(empty($value) && $notAllowEmpty)
			return $defaultValue;

		return $value;
	}

	/**
	 * Gets variable from $_SERVER superglobal
	 *
	 * @param string $name
	 *
	 * @return string|null
	 */

	public function getServer($name) {

		if(isset($_SERVER[$name]))
			return $_SERVER[$name];

		return null;

	}

	/**
	 * Checks whether $_REQUEST superglobal has certain index
	 *
	 * @param string $name
	 *
	 * @return bool
	 */

	public function has($name) {

		return isset($_REQUEST[$name]);
	}

	/**
	 * Checks whether $_POST superglobal has certain index
	 *
	 * @param string $name
	 *
	 * @return bool
	 */

	public function hasPost($name) {

		return isset($_POST[$name]);
	}

	/**
	 * Checks whether the PUT data has certain index
	 *
	 * @param string $name
	 *
	 * @return bool
	 */

	public function hasPut($name) {

		$put = $this->getPut();

		return isset($put[$name]);
	}

	/**
	 */

	/**
	 * Checks whether $_GET superglobal has certain index
	 *
	 * @param $name
	 *
	 * @return bool
	 */

	public function hasQuery($name) {

		return isset($_GET[$name]);
	}

	/**
	 * Checks whether $_SERVER superglobal has certain index
	 *
	 * @param $name
	 *
	 * @return bool
	 */

	public final function hasServer($name) {

		return isset($_SERVER[$name]);
	}

    /**
     * Checks whether headers has certain index
     *
     * @param $name
     *
     * @return bool
     */

    public final function hasHeader($header) {

    	$name = strtoupper(strtr($header, '-', '_'));

    	if(isset($_SERVER[$name]))
    		return true;

        if(isset($_SERVER['HTTP_' . $name]))
	        return true;

        return false;
    }

	/**
	 * Gets HTTP header from request data
	 *
	 * @param string $header
	 *
	 * @return string|null
	 */

	public final function getHeader($header) {

		$name = strtoupper(strtr($header, '-', '_'));

		if(isset($_SERVER[$name]))
			return $_SERVER[$name];

		if(isset($_SERVER['HTTP_' . $name]))
			return $_SERVER['HTTP_' . $name];

		return null;
	}

	/**
	 * Gets HTTP schema (http/https)
	 *
	 * @return string
	 */

	public function getScheme() {

		$https = $this->getServer('HTTPS');

		$scheme = 'http';

		if($https) {

			$scheme = 'https';

			if($https == 'off')
				$scheme = 'http';

		}

		return $scheme;
	}

	/**
	 * Checks whether request has been made using ajax
	 */

	public function isAjax() {

		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Checks whether request has been made using any secure layer
	 */

	public function isHTTPS() {

		return $this->getScheme() == 'https';
	}

	/**
	 * Gets HTTP raw request body
	 *
	 * @return string
	 */

	public function getRawBody() {

		if(empty($this->_rawBody)) {

			$contents = file_get_contents('php://input');

			$this->_rawBody = $contents;

			return $contents;
		}

		return $this->_rawBody;
	}

	/**
	 * Gets decoded JSON HTTP raw request body
	 *
	 * @return bool|array|\stdClass
	 */

	public function getJsonRawBody($asociative = false) {

		$rawBody = $this->getRawBody();

		if(is_string($rawBody))
			return false;

		return json_decode($rawBody, $asociative);
	}

	/**
	 * Gets active server name
	 *
	 * @return string
	 */

	public function getServerName() {

		if(isset($_SERVER['SERVER_NAME']))
			return $_SERVER['SERVER_NAME'];

		return 'localhost';
	}

	/**
	 * Gets host name used by the request.
	 *
	 * @return string
	 */

	public function getHttpHost() {

		$host = $this->getServer('HTTP_HOST');

		if(!$host) {

			$host = $this->getServer('SERVER_NAME');

			if(!$host)
				$host = $this->getServer('SERVER_ADDR');

		}

		return $host;
	}

	/**
	 * Gets HTTP method
	 *
	 * @return string
	 */

	public final function getMethod() {

		if(isset($_SERVER['REQUEST_METHOD']))
			$method = strtoupper($_SERVER['REQUEST_METHOD']);
		else
			return 'GET';

		if($method == 'POST') {

			$overrideMethod = $this->getHeader('X-HTTP-METHOD-OVERRIDE');

			if(!empty($overrideMethod)) {

				$method = strtoupper($overrideMethod);

			} else if ($this->_httpMethodParameterOverride) {

				if(isset($_REQUEST['_method']))
					$method = strtoupper($_REQUEST['_method']);

			}

		}

		return $method;
	}

	/**
	 * Check if HTTP method match any of the passed methods
	 * When strict is true it checks if validated methods are real HTTP methods
	 *
	 * @param string|array $methods
	 *
	 * @return boolean
	 */

	public function isMethod($methods) {

		$httpMethod = $this->getMethod();

		if(is_string($methods))
			return ($methods == $httpMethod);

		else if(is_array($methods)) {

			foreach($methods as $method) {

				if($this->isMethod($method))
					return true;

			}

			return false;

		}

		return false;
	}

	/**
	 * Check if HTTP method is POST
	 *
	 * @return bool
	 */

	public function isPost() {

		return $this->getMethod() == 'POST';
	}

	/**
	 * Checks if HTTP method is GET
	 *
	 * @return bool
	 */
	public function isGet() {

		return $this->getMethod() == 'GET';
	}

	/**
	 * Checks if HTTP method is PUT
	 *
	 * @return bool
	 */

	public function isPut() {

		return $this->getMethod() == 'PUT';
	}

	/**
	 * Check if HTTP method is DELETE
	 *
	 * @return bool
	 */

	public function isDelete() {

		return $this->getMethod() == 'DELETE';
	}

	/**
	 * Check if HTTP method is OPTIONS
	 *
	 * @return bool
	 */

	public function isOptions() {

		return $this->getMethod() == 'OPTIONS';
	}

	/**
	 * Checks whether request include attached files
	 */

	public function hasFiles() {

		return count($_FILES) > 0;

	}

	/**
	 * Gets attached files as Mike\Http\Request\File instances
	 *
	 * @param bool $onlySuccessful
	 *
	 * @return \Mike\Http\Request\File[]
	 */

	public function getUploadedFiles($onlySuccessful = false) {

		$files = [];

		// TODO: Dodělat uploaded _FILES!

		/*

		if(count($_FILES) > 0) {

			foreach($_FILES as $prefix => $input) {

				if(is_array($input['name'])) {

					$

				}

				if typeof input["name"] == "array" {
					let smoothInput = this->smoothFiles(
						input["name"],
						input["type"],
						input["tmp_name"],
						input["size"],
						input["error"],
						prefix
					);

					for file in smoothInput {
						if onlySuccessful == false || file["error"] == UPLOAD_ERR_OK {
							let dataFile = [
								"name": file["name"],
								"type": file["type"],
								"tmp_name": file["tmp_name"],
								"size": file["size"],
								"error": file["error"]
							];

							let files[] = new File(dataFile, file["key"]);
						}
					}
				} else {
					if onlySuccessful == false || input["error"] == UPLOAD_ERR_OK {
						let files[] = new File(input, prefix);
					}
				}
			}
		}
		*/

		return $files;
	}

}