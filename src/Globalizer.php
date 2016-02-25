<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress;


class Globalizer
{
  /**
   * CLears out all cache files for globalizer
   *
   */
  static public function clearCache()
  {
    $themes      = dirname(get_template_directory());
    $cacheDir    = $themes . '/cache/globalizer/';

    static::recursiveDelete($cacheDir);
  }

  static private function recursiveDelete($dir)
  {
    foreach(glob($dir . '/*') as $file) {
      if (is_dir($file)) {
        static::recursiveDelete($file);
      } else {
        unlink($file);
      }
    }
    rmdir($dir);
  }

  /**
   * Generates a cache file for a class
   *
   * @param $class
   */
  static public function generateCache($class)
  {
    $classToFile = str_replace('\\','/',$class) . '.php';
    $nsDir       = dirname($classToFile);
    $cacheFile   = WP_CONTENT_DIR . '/cache/globalizer/' . $classToFile;

    if ( !is_dir(WP_CONTENT_DIR . '/cache/globalizer/' . $nsDir )) {
      mkdir(WP_CONTENT_DIR . '/cache/globalizer/' . $nsDir,0777,true);
    }

    $code = static::getClassStubs($class);

    if ( trim($code)) {
      file_put_contents($cacheFile,$code);
    }
  }

  /**
   *
   * @param $class
   */
  static public function writeCache($class)
  {
    $code = Globalizer::getClassStubs($class);

    if ( empty($code)) {
      return;
    }

    $classToFile = str_replace('\\','/',$class) . '.php';
    $nsDir       = dirname($classToFile);
    $cacheFile   = WP_CONTENT_DIR . '/cache/globalizer/' . $classToFile;

    if ( !is_dir(WP_CONTENT_DIR . '/cache')) {
      mkdir(WP_CONTENT_DIR . '/cache');
    }

    if ( !is_dir(WP_CONTENT_DIR . '/cache/globalizer')) {
      mkdir(WP_CONTENT_DIR . '/cache/globalizer');
    }

    if ( !is_dir(WP_CONTENT_DIR . '/cache/globalizer/' . $nsDir )) {
      mkdir(WP_CONTENT_DIR . '/cache/globalizer/' . $nsDir,0777,true);
    }

    file_put_contents($cacheFile,$code);
  }

  /**
   *
   * @param $class
   * @return bool
   */
  static private function loadCache($class)
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      return false;
    }

    $classToFile = str_replace('\\','/',$class) . '.php';
    $cacheFile   = WP_CONTENT_DIR . '/cache/globalizer/' . $classToFile;

    if ( file_exists($cacheFile)) {
      require_once($cacheFile);
      return true;
    }
    return false;
  }

  /**
   * Generate stub code for a class
   */
  static public function getClassStubs($class)
  {
    $mapping = self::getGlobalizeClassMethods($class);

    if ( empty($mapping) ) {
      return false;
    }

    $code = "<?php\n// this code was generated, please do not modify manually\n\n";

    foreach($mapping as $globFun => $method) {
      $code .= "// $method => $globFun\n";
      $code .= self::getAliasFunction($method,$globFun) . "\n\n";
    }
    return $code;
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
    if ( static::loadCache($class)) {
      return;
    }

    $mapping = self::getGlobalizeClassMethods($class);

    if ( $mapping === false ) {
      return false;
    }

    foreach($mapping as $globFun => $method) {
      self::createAliasFunction($method,$globFun);
    }
    return true;
  }

  /**
   * Checks a class for globalize methods and returns a list of methods and their global function name
   *
   * @param $class
   * @return array|bool
   */
  static public function getGlobalizeClassMethods($class)
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

    eval(self::getAliasFunction($methodName,$aliasName));
    return true;
  }

  /**
   * Creates a stub template for wrapping the static call
   *
   * @param $methodName
   * @param $aliasName
   * @return string
   */
  static protected function getAliasFunction($methodName, $aliasName)
  {
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
        } else if ( is_null($val)) {
          $val = 'null';
        }

        $fproto .= ' = '.$val;
      }
      $need_comma = true;
    }
    $fproto .= ')';
    $fcall  .= ')';

    $f = "function $fproto {return ".$fcall.';}';
    return $f;
  }
}