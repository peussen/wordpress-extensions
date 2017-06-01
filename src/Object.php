<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 11/03/15
 * Time: 17:02
 */

namespace Woppe\Wordpress;

if ( version_compare(PHP_VERSION,'7.0.0','<') ) {

  class Object
  {
    /**
     * Converts a list of object to an associative array
     *
     * @param $objectList
     * @param $keyAttribute
     * @param $valAttribute
     * @return array
     */
    static public function listToKeyValue($objectList, $keyAttribute, $valAttribute)
    {
      $tmp = array();

      foreach ($objectList as $object) {

        $key = $val = null;

        if (isset($object->$keyAttribute)) {
          $key = $object->$keyAttribute;
        } else if (is_callable(array($object, $keyAttribute))) {
          $key = call_user_func(array($object, $keyAttribute));
        } elseif (method_exists($object, '__get')) {
          $key = $object->__get($keyAttribute);
        } else {
          throw new \InvalidArgumentException("Could not locate key $keyAttribute");
        }

        if (isset($object->$valAttribute)) {
          $val = $object->$valAttribute;
        } else if (is_callable(array($object, $valAttribute))) {
          $val = call_user_func(array($object, $valAttribute));
        } elseif (method_exists($object, '__get')) {
          $val = $object->__get($valAttribute);
        } else {
          throw new \InvalidArgumentException("Could not locate value $valAttribute");
        }

        $tmp[(string)$key] = $val;
      }

      return $tmp;
    }

    /**
     * Builds a list of attribute values from a list of objects
     * Creates an array from a list of objects, and reads the $key attribute from
     * the array and stores it in the result array.
     * With the $asString set to TRUE, this will return a comma seperated string,
     * otherwise it will return an array.
     *
     * @param array $arrayOfObjects
     * @param       $key
     * @param bool $asString
     *
     * @return array|string
     */
    static public function createlist(array $arrayOfObjects, $key, $asString = true)
    {
      $tmp = array();
      foreach ($arrayOfObjects as $object) {
        if (isset($object->$key)) {
          $tmp[] = $object->$key;
        }
      }

      if ($asString) {
        return implode(',', $tmp);
      }
      return $tmp;
    }

  }
}