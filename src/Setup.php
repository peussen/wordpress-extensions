<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 13/02/15
 * Time: 13:50
 */

namespace HarperJones\Wordpress;

class Setup
{
	static private $container = null;

	/**
	 * Directly set a value in the container
	 * When the setting is already there, you will be given an error unless
	 * the $force parameter is set to TRUE
	 *
	 * @param      $attribute
	 * @param      $val
	 * @param bool $force
	 */
	static public function set($attribute,$val,$force = false)
	{
		$container = self::getContainer();

		if ( isset($container[$attribute]) && !$force) {
			throw new \InvalidArgumentException("Attribute $attribute already set in container");
		}

		$container[$attribute] = $val;
	}

	/**
	 * Obtains a value from the container, or returns the default value if it is not
	 *
	 * @param      $attribute
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	static public function get($attribute, $default = null)
	{
		$container = self::getContainer();

		if ( isset($container[$attribute])) {
			return $container[$attribute];
		}
		return $default;
	}

	static public function getContainer()
	{
		if ( self::$container === null ) {
			// Foutief gebruik van een DI container, maar het is gewoon een storage container nu
			self::$container = new \Pimple\Container();
		}
		return self::$container;
	}

	/**
	 * Strips the namespace part of the class
	 *
	 * @param $class
	 *
	 * @return string
	 */
	static public function deNamespace($class)
	{
		$class = trim($class, '\\');
		if ($last_separator = strrpos($class, '\\'))
		{
			$class = substr($class, $last_separator + 1);
		}
		return $class;
	}

	/**
	 * Obtains the namespace part of a class name
	 *
	 * @param $class
	 *
	 * @return string
	 */
	static public function getNamespace($class)
	{
		$class = trim($class, '\\');
		if ($last_separator = strrpos($class, '\\'))
		{
			return substr($class, 0, $last_separator + 1);
		}
		return '';
	}

	/**
	 * Converts static method from a class into global methods (based on the @globalize doc)
	 * Reflects a class and obtains all static public methods, and checks them for the
	 * @globalize tag in the docblock. If it is there, it will create a global function based
	 * on the parameter of the @globalize tag.
	 *
	 * If no parameters was passed with the @globalize tag, it will create one from the
	 * full class name (including namespaces).
	 *
	 * After execution this method will return FALSE if reflection failed and an array with the
	 * created mappings when it succeeded.
	 *
	 * @param $class
	 *
	 * @return array|bool
	 */
	static public function globalizeStaticMethods($class)
	{
		try {
			$reflection = new \ReflectionClass($class);
			$methods    = $reflection->getMethods(\ReflectionMethod::IS_STATIC);
			$mapping    = array();

			foreach( $methods as $method ) {
				if ( $method->isPublic()) {
					$docblock = $method->getDocComment();

					if ( preg_match('/\*\s+@globalize\s*([a-zA-Z0-9_]+)?/ms',$docblock,$matches)) {

						if ( isset($matches[1])) {
							$global_function = $matches[1];
						} else {
							$global_function = strtolower(str_replace('\\','_',$method->class) . $method->name);
						}
						self::createAliasFunction($method->class . '::' . $method->name,$global_function);
						$mapping[$global_function] = $method->class . '::' . $method->name;
					}
				}
			}
			return $mapping;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Aliases a method to a global function
	 *
	 * @param string $methodName
	 * @param string $aliasName
	 *
	 * @return bool
	 */
	static function createAliasFunction($methodName, $aliasName)
	{
		if( function_exists($aliasName) )
			return false;

		$rf         = new \ReflectionMethod($methodName);
		$fproto     = $aliasName.'(';
		$fcall      = $methodName.'(';
		$need_comma = false;

		foreach($rf->getParameters() as $param)
		{
			if($need_comma)
			{
				$fproto .= ',';
				$fcall .= ',';
			}

			$fproto .= '$'.$param->getName();
			$fcall .= '$'.$param->getName();

			if($param->isOptional() && $param->isDefaultValueAvailable())
			{
				$val = $param->getDefaultValue();
				if(is_string($val)) {
					$val = "'" . addslashes($val) . "'";
				} else if ( is_bool($val)) {
					$val = $val ? 'true' : 'false';
				}
				$fproto .= ' = '.$val;
			}
			$need_comma = true;
		}
		$fproto .= ')';
		$fcall  .= ')';

		$f = "function $fproto {return ".$fcall.';}';

		eval($f);
		return true;
	}

}