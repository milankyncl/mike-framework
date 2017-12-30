<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Utilities;


class Text {

	const RANDOM_ALNUM = 0;

	const RANDOM_ALPHA = 1;

	const RANDOM_HEXDEC = 2;

	const RANDOM_NUMERIC = 3;

	const RANDOM_NOZERO = 4;

	const RANDOM_DISTINCT = 5;

	/**
	 * Convert text to CamelCase
	 *
	 * @param $str string
	 * @param $delimiter string
	 *
	 * return string
	 */

	public static function camelize($str, $delimiter = '-') {

		$words = explode($delimiter, $str);
		$words = array_map('ucfirst', $words);
		$string = implode('', $words);

		return $string;
	}

	/**
	 * Uncamelize string
	 *
	 * @param $str string
	 * @param $delimiter string
	 *
	 * @return string
	 */

	public static function uncamelize($str, $delimiter = '_') {

		$string = preg_replace('/[A-Z]/', $delimiter . '$0', $str);
	    $string = strtolower($string);
	    $string = ltrim($string, $delimiter);

	    return $string;
	}

	/**
	 * Generates a random string, based on type
	 *
	 * @param int $type
	 * @param int $length
	 *
	 * @return string
	 */

	public static function random($type = 0, $length = 8) {

		$str = '';

		switch($type) {

			case Text::RANDOM_HEXDEC:
				$pool = array_merge(range(0, 9), range('a', 'f'));
				break;

			case Text::RANDOM_NUMERIC:
				$pool = range(0, 9);
				break;

			case Text::RANDOM_NOZERO:
				$pool = range(1, 9);
				break;

			case Text::RANDOM_DISTINCT:
				$pool = str_split('2345679ACDEFHJKLMNPRSTUVWXYZ');
				break;

			case Text::RANDOM_ALPHA:
			default:
				$pool = array_merge(range('a', 'z'), range('A', 'Z'));

		}

		$end = count($pool) - 1;

		while(strlen($str) < $length)
			$str .= $pool[mt_rand(0, $end)];

		return $str;
	}

	/**
	 * Check if a string starts with a given string
	 *
	 * @param $str
	 * @param $start
	 * @param bool $ignoreCase
	 *
	 * @return bool
	 */

	public static function startsWith($str, $start, $ignoreCase = true) {

		if($ignoreCase) {

			$str = self::lowercase($str);
			$start = self::lowercase($start);
		}

		return substr($str, 0, strlen($start)) == $start;
	}

	/**
	 * Check if a string ends with a given string
	 *
	 * @param $str
	 * @param $end
	 * @param bool $ignoreCase
	 *
	 * @return bool
	 */

	public static function endsWith($str, $end, $ignoreCase = true) {

		if($ignoreCase) {

			$str = self::lowercase($str);
			$end = self::lowercase($end);
		}

		return substr($str, 0, -strlen($end)) == $end;
	}

	/**
	 * Lowercase string
	 *
	 * @param $str
	 * @param string $encoding
	 *
	 * @return string
	 */

	public static function lowercase($str, $encoding = 'UTF-8') {

		if(function_exists('mb_strtolower'))
			return mb_strtolower($str, $encoding);

		return strtolower($str);
	}

	/**
	 * Uppercases a string, this function makes use of the mbstring extension if available
	 *
	 * @param string $str
	 * @param string $encoding
	 *
	 * @return string
	 */

	public static function uppercase($str, $encoding = 'UTF-8') {

		if(function_exists('mb_strtoupper'))
			return mb_strtoupper($str, $encoding);

		return strtoupper($str);
	}

	/**
	 * Reduces multiple slashes in a string to single slashes
	 *
	 * @param $str string
	 *
	 * @return null|string|string[]
	 */

	public static function reduceSlashes($str) {

		return preg_replace("#(?<!:)//+#", '/', $str);
	}

	/**
	 * Humanize text
	 *
	 * @param $text string
	 *
	 * @return null|string|string[]
	 */

	public static function humanize($str) {

		return preg_replace("#[_-]+#", ' ', trim($str));
	}

	/**
	 * Check if string contains queried string
	 *
	 * @param $str string
	 * @param $query string
	 *
	 * @return bool
	 */

	public static function contains($str, $query) {

		return (strpos($str, $query) !== false);
	}

}