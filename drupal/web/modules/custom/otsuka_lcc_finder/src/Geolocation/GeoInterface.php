<?php

namespace Drupal\otsuka_lcc_finder\Geolocation;

/**
 * Provides a Geo interface class.
 */
interface GeoInterface {

  /**
   * Request method for interface.
   *
   * @param string $data
   *   Incoming data.
   *
   * @return array|bool
   *   Return data
   */
  public function sendRequest($data);

}
