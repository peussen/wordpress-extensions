<?php
/*
 * @author: petereussen
 * @package: darionwm
 */

namespace HarperJones\Wordpress\Services;


/**
 * Geo IP implementation based on http://freegeoip.net/
 *
 * @package DarionWM
 */
class GeoIP
{
  const ENDPOINT = 'http://freegeoip.net/json/';

  public function getRemoteAddr()
  {
    if ( isset($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( isset($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    } else {
      $ip = '127.0.0.1';
    }

    return $this->query($ip);
  }

  public function queryRemoteIP()
  {
    $ip = $this->getRemoteAddr();
    return $this->query($ip);
  }

  public function query($ip)
  {
    $queryUrl = self::ENDPOINT . urlencode($ip);
    $client   = curl_init($queryUrl);

    curl_setopt($client,CURLOPT_HEADER,false);
    curl_setopt($client,CURLOPT_FAILONERROR,true);
    curl_setopt($client,CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($client,CURLOPT_RETURNTRANSFER,true);

    $response = curl_exec($client);

    if ( $response === false ) {
      $exception = new \RuntimeException(
        curl_error($client),
        curl_errno($client)
      );
      curl_close($client);
      throw $exception;
    }

    curl_close($client);
    return json_decode($response);
  }
}