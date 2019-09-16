<?php

namespace Drupal\otsuka_lcc_finder\Geolocation;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

/**
 * Provides a Google geo class.
 */
class Google implements GeoInterface {

  const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

  /**
   * The http client request.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Geolocation settings config instance.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $geolocationSettings;

  /**
   * Constructs a google geo class.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The http client service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {
    $this->httpClient = $http_client;
    $this->geolocationSettings = $config_factory->get('geolocation.settings');
  }

  /**
   * Google geo search by zip code.
   *
   * @param string $data
   *   Search string.
   *
   * @return array|bool
   *   Coordinates array or FALSE
   */
  public function sendRequest($data) {
    $api_key = '';
    $url = self::API_URL . '?address=' . urlencode($data);

    if (!empty($this->geolocationSettings->get('google_map_api_server_key'))) {
      $api_key = $this->geolocationSettings->get('google_map_api_server_key');
    }
    elseif (!empty($this->geolocationSettings->get('google_map_api_key'))) {
      $api_key = $this->geolocationSettings->get('google_map_api_key');
    }
    if ($api_key) {
      $url .= '&key=' . $api_key;
    }
    $response = $this->httpClient->get($url)->getBody();
    $geocodeData = json_decode($response);
    $coordinates = FALSE;
    if (!empty($geocodeData) && $geocodeData->status != 'ZERO_RESULTS' && isset($geocodeData->results) && isset($geocodeData->results[0])) {
      $coordinates = [
        'lat' => $geocodeData->results[0]->geometry->location->lat,
        'lng' => $geocodeData->results[0]->geometry->location->lng,
        'zip' => '',
        'country' => '',
      ];
      foreach ($geocodeData->results[0]->address_components as $address_component) {
        if (in_array('postal_code', $address_component->types)) {
          $coordinates['zip'] = $address_component->short_name;
        }
        if (in_array('country', $address_component->types)) {
          $coordinates['country'] = $address_component->short_name;
        }
      }
    }

    return $coordinates;
  }

}
