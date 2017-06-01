<?php
/*
 * @author: petereussen
 * @package: pressmarket
 */

namespace Woppe\Wordpress;

class Str
{
  /**
   * Truncates a string to a specified number of words
   *
   * @param $text
   * @param int $limit
   * @param string $more
   * @return string
   */
  static public function trunc($text,$limit = 50,$more = '...')
  {
    if (str_word_count($text, 0) > $limit) {
      $words = str_word_count($text, 2);
      $pos   = array_keys($words);
      $text  = substr($text, 0, $pos[$limit]) . $more;
    }
    return $text;
  }
}
