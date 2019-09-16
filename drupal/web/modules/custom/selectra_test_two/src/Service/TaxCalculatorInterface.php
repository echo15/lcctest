<?php

namespace Drupal\selectra_test_two\Service;

interface TaxCalculatorInterface {

  /**
   * Calculates full product price.
   *
   * @param int $product_id
   *   Product Entity id.
   *
   * @return float
   *   Product price with taxes.
   */
  public function calculateProductFullPrice($product_id);

  /**
   * Calculates taxes and total for order.
   *
   * @param array $product_ids
   *   Array of Product Entity ids.
   *
   * @return array
   *   Array for total tax and total order values.
   */
  public function calculateOrderPrice($product_ids);

  /**
   * Calculates taxes for product.
   *
   * @param $product
   *   ProductEntity $product
   * @param array $price
   *   ProductEntity price
   *
   * @return array
   *   Array for total tax and total order values.
   */
  public function getProductTaxes($product, $price);

}
