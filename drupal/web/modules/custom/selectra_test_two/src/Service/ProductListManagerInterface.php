<?php
/**
 * Created by PhpStorm.
 * User: Dee
 * Date: 9/13/19
 * Time: 12:19 AM
 */

namespace Drupal\selectra_test_two\Service;

interface ProductListManagerInterface {

  /**
   * Creates List of available products.
   *
   * @return array
   *   Array of available products with prices.
   */
  public function createProductList();

}
