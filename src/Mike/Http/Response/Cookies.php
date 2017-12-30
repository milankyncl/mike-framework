<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http\Response;

use Mike\Http\Cookie;
use Mike\DependencyContainer\Injectable;


class Cookies extends Injectable {

	protected $_registered = false;

	protected $_useEncryption = true;

	/**
	 * @var Cookie[]
	 */

	protected $_cookies = [];

	/**
	 * Set if cookies in the bag must be automatically encrypted/decrypted
	 *
	 * @param bool $useEncryption
	 *
	 * @return Cookies
	 */

	public function useEncryption($useEncryption) {

		$this->_useEncryption = $useEncryption;

		return $this;
	}

	/**
	 * Sets a cookie to be sent at the end of the request
	 * This method overrides any cookie set before with the same name
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param int $expire
	 * @param string $path
	 * @param bool|null $secure
	 * @param string $domain
	 * @param bool $httpOnly
	 *
	 * @return Cookies
	 */

	public function set($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null) {

		$encryption = $this->_useEncryption;

		/**
		 * Check if the cookie needs to be updated or
		 */

		if(!isset($this->_cookies[$name])) {

			$cookie = new Cookie($name, $value, $expire, $path, $secure, $domain, $httpOnly);

			$cookie->setDC($this->_dependencyContainer);

			if($encryption)
				$cookie->useEncryption($encryption);

			$this->_cookies[$name] = $cookie;

		} else {

			$cookie = $this->_cookies[$name];

			// TODO: Opravit přepsání stávájící cookie v Interním bagu

			$cookie->setValue($value);
			$cookie->setExpiration($expire);
			$cookie->setPath($path);
			$cookie->setSecure($secure);
			$cookie->setDomain($domain);
			$cookie->setHttpOnly($httpOnly);
		}

		/**
		 * Register the cookies bag in the response
		 */

		if(!$this->_registered) {

			/** @var \Mike\Http\Response $response */

			$response = $this->_dependencyContainer->get('response');

			$response->setCookies($this);

			$this->_registered = true;

		}

		return $this;
	}

	/**
	 * Gets a cookie from the bag
	 *
	 * @param string $name
	 *
	 * @return Cookie|false
	 */

	public function get($name) {

		if(isset($this->_cookies[$name]))
			return $this->_cookies[$name];

		return false;
	}

	/**
	 * Check if a cookie is defined in the bag or exists in the _COOKIE superglobal
	 *
	 * @param string $name
	 *
	 * return bool
	 */

	public function has($name) {

		if(isset($this->_cookies[$name]))
			return true;

		if(isset($_COOKIE[$name]))
			return true;

		return false;
	}

	/**
	 * Deletes a cookie by its name
	 * This method does not removes cookies from the _COOKIE superglobal
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function delete($name) {


		if(isset($this->_cookies[$name])) {

			$this->_cookies[$name]->delete();

			return true;
		}

		return false;
	}

	/**
	 * Sends the cookies to the client
	 * Cookies aren't sent if headers are sent in the current request
	 */

	public function send() {

		if(!headers_sent()) {

			/** @var Cookie $cookie */

			foreach($this->_cookies as $cookie) {

				$cookie->send();

			}

			return true;
		}

		return false;
	}

	/**
	 * Reset set cookies
	 */

	public function reset() {

		$this->_cookies = [];

		return $this;
	}
}