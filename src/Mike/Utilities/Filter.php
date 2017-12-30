<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Utilities;

use Mike\Exception;
use Mike\DependencyContainer\Injectable;


class Filter extends Injectable {

	/**
	 * Filter constants
	 */

	const FILTER_EMAIL = 'email';

	const FILTER_ABSINT = 'absint';

	const FILTER_INT = 'int';

	const FILTER_INT_CAST = 'int!';

	const FILTER_STRING = 'string';

	const FILTER_FLOAT = 'float';

	const FILTER_FLOAT_CAST = 'float!';

	const FILTER_ALPHANUM = 'alphanum';

	const FILTER_TRIM = 'trim';

	const FILTER_STRIPTAGS = 'striptags';

	const FILTER_LOWER = 'lower';

	const FILTER_UPPER = 'upper';

	const FILTER_URL = 'url';

	const FILTER_SPECIAL_CHARS = 'special_chars';

	/**
	 * Sanitizes a value with a specified single or set of filters
	 *
	 * @param array|string $value
	 * @param array|string $filters
	 *
	 * @return array|string
	 *
	 * @throws Exception
	 */

	public function sanitize( $value, $filters) {

		if(is_array($filters)) {

			if(!is_null($value)) {

				foreach($filters as $filter) {

					return $this->_parseValue($value, $filter);

				}
			}

		} else {

			return $this->_parseValue($value, $filters);

		}

		return $value;

	}

	/**
	 * Parse value as a single value or array
	 *
	 * @param string|array $value
	 * @param string|array $filter
	 *
	 * @return array|string
	 *
	 * @throws Exception
	 */

	private function _parseValue($value, $filter) {

		if(is_array($value)) {

			$arrayValue = [];

			foreach($value as $key => $itemValue) {

				$arrayValue[$key] = $this->_sanitize($itemValue, $filter);
			}

			return $arrayValue;

		} else {

			return $this->_sanitize($value, $filter);
		}

	}


	private function _sanitize($value, $filter) {

		switch($filter) {

			case Filter::FILTER_EMAIL:

				return filter_var($value, FILTER_SANITIZE_EMAIL);

			case Filter::FILTER_INT:

				return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

			case Filter::FILTER_INT_CAST:

				return intval($value);

			case Filter::FILTER_ABSINT:

				return abs(intval($value));

			case Filter::FILTER_STRING:

				return filter_var($value, FILTER_SANITIZE_STRING);

			case Filter::FILTER_FLOAT:

				return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, [ 'flags' => FILTER_FLAG_ALLOW_FRACTION ]);

			case Filter::FILTER_FLOAT_CAST:

				return doubleval($value);

			case Filter::FILTER_ALPHANUM:

				return preg_replace("/[^A-Za-z0-9]/", '', $value);

			case Filter::FILTER_TRIM:

				return trim($value);

			case Filter::FILTER_STRIPTAGS:

				return strip_tags($value);

			case Filter::FILTER_LOWER:

				return Text::lowercase($value);

			case Filter::FILTER_UPPER:

				return Text::uppercase($value);

			case Filter::FILTER_URL:

				return filter_var($value, FILTER_SANITIZE_URL);

			case Filter::FILTER_SPECIAL_CHARS:

				return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);

			default:

				throw new Exception('Sanitize filter \'' . $filter . '\' is not supported.');

		}

	}

}
