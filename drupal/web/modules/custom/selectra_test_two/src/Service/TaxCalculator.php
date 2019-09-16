<?php

namespace Drupal\selectra_test_two\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\selectra_test_two\Entity\ProductEntity;

class TaxCalculator implements TaxCalculatorInterface {

  protected $entityTypeManager;

  /**
   * Object constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
 * {@inheritdoc}
 */
  public function calculateProductFullPrice($product_id) {
    /** @var ProductEntity $product */
    $product = $this->entityTypeManager->getStorage('product_entity')->load($product_id);
    $tax_entities = $this->entityTypeManager->getStorage('tax_entity')->loadMultiple();
    $price = $product->getProductPrice();
    $is_imported = $product->isImported();
    $final_price = $price;
    $additions = [];
    $product_type = $product->getProductType();
    if ($tax_entities) {
      foreach ($tax_entities as $key => $tax_entity) {
        $types = $tax_entity->get('product_types');
        $types = array_keys($types);
        if ($key == 'default_tax') {
          if (in_array($product_type, $types)) {
            $percentage = $tax_entity->get('percentage');
            $addition = $price/100 * $percentage;
            $additions[] = $addition;
          }
        }
        else {
          if ($is_imported) {
            $percentage = $tax_entity->get('percentage');
            $addition = $price/100 * $percentage;
            $additions[] = $addition;
          }
        }
      }
      if ($additions && is_array($additions)) {
        foreach ($additions as $key => $addition) {
          $final_price = $final_price + $addition;
          $final_price = round($final_price ,2);
        }
      }


    }
    return $final_price;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOrderTaxes($product_ids) {

  }
}
