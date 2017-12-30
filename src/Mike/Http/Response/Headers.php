<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http\Response;

use Mike\Utilities\Text;


class Headers {

	protected $_headers = [];

	/**
	 * Sets a header to be sent at the end of the request
	 *
	 * @param string $name
	 * @param string $value
	 */

	public function set($name, $value) {

		$this->_headers[$name] = $value;
	}

	/**
	 * Gets a header value from the internal bag
	 *
	 * @param string $name
	 *
	 * @return string|boolean
	 */

	public function get($name) {

		return (isset($this->_headers[$name]) ? $this->_headers[$name] : false);
	}

	/**
	 * Sets a raw header to be sent at the end of the request
	 *
	 * @param string $header
	 */

	public function setRaw($header) {

		$this->_headers[$header] = null;
	}

	/**
	 * Removes a header to be sent at the end of the request
	 *
	 * @param string $header
	 */

	public function remove($header) {

		unset($this->_headers[$header]);
	}

	/**
	 * Sends the headers to the client
	 *
	 * @return bool
	 */

	public function send() {

		if(!headers_sent()) {

			foreach($this->_headers as $header => $value) {

				if(!is_null($value)) {

					header($header . ': ' . $value,true);

				} else {

					if(Text::contains($header, ':') || substr($header, 0, 5) == 'HTTP/')
						header($header, true);

					else
						header($header . ': ', true);

				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Reset set headers
	 */

	public function reset() {

		$this->_headers = [];
	}

	/**
	 * Returns the current headers as an array
	 *
	 * @return array
	 */

	public function toArray() {

		return $this->_headers;
	}

	/**
	 * Restore a \Mike\Http\Response\Headers object
	 *
	 * @param array $data
	 *
	 * @return Headers
	 */

	public static function __set_state($data) {

		$headers = new self();

		if(isset($data['_headers'])) {

			foreach($data['_headers'] as $key => $value)
				$headers->set($key, $value);

		}

		return $headers;
	}

}