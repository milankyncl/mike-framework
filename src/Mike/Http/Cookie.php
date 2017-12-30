<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http;

use Mike\Exception;
use Mike\DependencyContainer\Injectable;


class Cookie extends Injectable {

	/** @var \Mike\Utilities\Filter $_filter */

	protected $_filter;

	protected $_restored = false;

	protected $_useEncryption = false;

	protected $_name;

	protected $_value;

	protected $_expire;

	protected $_path = '/';

	protected $_domain;

	protected $_secure;

	protected $_httpOnly = true;

	/**
	 * Cookie constructor
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param int $expire
	 * @param string $path
	 * @param boolean $secure
	 * @param string $domain
	 * @param boolean $httpOnly
	 */

	public function __construct($name, $value = null, $expire = 0, $path = '/', $secure = null, $domain = null, $httpOnly = false) {

		$this->_name = $name;
		$this->_value = $value;
		$this->_expire = $expire;
		$this->_path = $path;
		$this->_secure = $secure;
		$this->_domain = $domain;
		$this->_httpOnly = $httpOnly;
	}

	/**
	 * Sets the cookie's value
	 *
	 * @param string value
	 *
	 * @return Cookie
	 */

	public function setValue($value) {

		$this->_value = $value;

		return $this;
	}

	/**
	 * Returns the cookie's value
	 *
	 * @param string|array filters
	 * @param string defaultValue
	 * @return mixed
	 */

	public function getValue($filters = null, $defaultValue = null) {

		if(!$this->_restored)
			$this->restore();

		if(isset($_COOKIE[$this->_name])) {

			$value = $_COOKIE[$this->_name];

			if($this->_useEncryption) {

				$crypt = $this->_dependencyContainer->get('crypt');

				/**
				 * Decrypt the value also decoding it with base64
				 */

				$decryptedValue = $crypt->decryptBase64($value);

			} else {

				$decryptedValue = $value;
			}

			/**
			 * Update the decrypted value
			 */

			$this->_value = $decryptedValue;

			if(!is_null($filters)) {

				if(!isset($this->_filter))
					$this->_filter = $this->_dependencyContainer->get('filter');

				$filter = $this->_filter;

				return $filter->sanitize($decryptedValue, $filters);
			}

			return $decryptedValue;
		}

		return $this->_value;
	}

	/**
	 * Sends the cookie to the HTTP client
	 * Stores the cookie definition in session
	 *
	 * @return Cookie
	 */

	public function send() {

		$name = $this->_name;
		$value = $this->_value;
		$expire = $this->_expire;
		$domain = $this->_domain;
		$path = $this->_path;
		$secure = $this->_secure;
		$httpOnly = $this->_httpOnly;

		$definition = [];

		if($expire != 0)
			$definition['expire'] = $expire;

		if(!empty($path))
			$definition['path'] = $path;

		if(!empty($domain))
			$definition['domain'] = $domain;

		if(!empty($secure))
			$definition['secure'] = $secure;

		if(!empty($httpOnly))
			$definition['httpOnly'] = $httpOnly;

		/**
		 * The definition is stored in session
		 */

		if(!empty($definition)) {

			$session = $this->_dependencyContainer->get('session');

			if($session->isStarted())
				$session->set('_PHCOOKIE_' . $name, $definition);

		}

		if($this->_useEncryption) {

			if(!empty($value)) {

				$crypt = $this->_dependencyContainer->get('crypt');

				$encryptValue = $crypt->encryptBase64($value);

			} else {

				$encryptValue = $value;

			}

		} else {

			$encryptValue = $value;
		}

		/**
		 * Sets the cookie using the standard 'setcookie' function
		 */

		setcookie($name, $encryptValue, $expire, $path, $domain, $secure, $httpOnly);

		return $this;
	}

	/**
	 * Reads the cookie-related info from the SESSION to restore the cookie as it was set
	 * This method is automatically called internally so normally you don't need to call it
	 *
	 * @return Cookie
	 */

	public function restore() {

		if(!$this->_restored) {

			$session = $this->_dependencyContainer->get('session');

			if($session->isStarted()) {

				$definition = $session->get('_PHCOOKIE_' . $this->_name);

				if(is_array($definition)) {

					if(isset($definition['expire']))
						$this->_expire = $definition['expire'];

					if(isset($definition['domain']))
						$this->_domain = $definition['domain'];

					if(isset($definition['expire']))
						$this->_expire = $definition['path'];

					if(isset($definition['secure']))
						$this->_secure = $definition['secure'];

					if(isset($definition['httpOnly']))
						$this->_httpOnly = $definition['httpOnly'];
				}
			}

			$this->_restored = true;
		}

		return $this;
	}

	/**
	 * Deletes the cookie by setting an expire time in the past
	 */

	public function delete() {

		$session = $this->_dependencyContainer->get('session');
		$session->remove('_PHCOOKIE_' . $this->_name);

		$this->_value = '';

		setcookie($this->_name, null);
		unset($_COOKIE[$this->_name]);
	}

	/**
	 * Sets if the cookie must be encrypted/decrypted automatically
	 *
	 * @param bool $useEncryption
	 *
	 * @return Cookie
	 */

	public function useEncryption($useEncryption) {

		$this->_useEncryption = $useEncryption;

		return $this;
	}

	/**
	 * Magic __toString method converts the cookie's value to string
	 *
	 * @return string
	 */

	public function __toString() {

		return $this->getValue();
	}
}