<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 12/03/15
 * Time: 09:38
 */

namespace HarperJones\Wordpress;


final class Arr
{
	/**
	 * Obtains a value from an (multidimensional) array
	 * This function can either take a string or an array as $key
	 * parameter. If it is an array, every value in that array will
	 * be used as key.
	 *
	 * @param array $array
	 * @param mixed $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	static public function value(array $array, $key, $default = null)
	{
		if ( is_array($key) ) {
			$curKey = array_shift($key);
			$nextKey= $key;
		} else {
			$curKey = $key;
			$nextKey= array();
		}

		if ( isset($array[$curKey])) {
			if ( $nextKey ) {
				return self::value($array[$curKey],$nextKey,$default);
			} else {
				return $array[$curKey];
			}
		}
		return $default;
	}

	/**
	 * Returns the value if the array has a value under $key, or false if not
	 *
	 * @param array $array
	 * @param       $key
	 * @param       $otherwise
	 *
	 * @return mixed
	 */
	static public function noValue(array $array, $key, $otherwise = true)
	{
		$val = self::value($array,$key);

		if ( empty($val)) {
			return $otherwise;
		}
		return false;
	}

	/**
	 * Converts a text into a key/value array
	 * Converts a string into key/value pairs. Each key/value pair
	 * should be placed on a newline. And the key and value should
	 * be seperated by an '='. Extra '=' characters in the value
	 * will be ignored
	 *
	 * @param string $text
	 * @param string $keySeperator (default '=')
	 *
	 * @return array
	 */
	static public function textToKeyValue($text,$keySeperator = '=') {
		$array = array();
		$lines = explode( "\n", $text );

		foreach ( $lines as $line ) {
			$line   = trim( $line );
			$blocks = explode( $keySeperator, $line );

			if ( count( $blocks ) >= 2 ) {
				$key           = trim(array_shift( $blocks ));
				$val           = implode( $keySeperator, $blocks );
				$array[ $key ] = $val;
			}
		}

		return $array;
	}


	/**
	 * Prepends an entry before an associative array
	 *
	 * @param array $list
	 * @param       $key
	 * @param       $value
	 *
	 * @return array
	 */
	static public function prependAssoc(array $list, $key, $value )
	{
		$new = array($key => $value);

		foreach( $list as $addKey => $addValue) {
			$new[$addKey] = $addValue;
		}
		return $new;
	}
}