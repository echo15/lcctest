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
  public function getProductTaxes($product, $price) {
    /** @var ProductEntity $product */
    $tax_entities = $this->entityTypeManager->getStorage('tax_entity')->loadMultiple();
    $is_imported = $product->isImported();
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
    }
    return $additions;
  }

  /**
 * {@inheritdoc}
 */
  public function calculateProductFullPrice($product_id) {
    /** @var ProductEntity $product */
    $product = $this->entityTypeManager->getStorage('product_entity')->load($product_id);
    $price = $product->getProductPrice();
    $additions = $this->getProductTaxes($product, $price);

    if ($additions && count($additions)) {
      foreach ($additions as $key => $addition) {
        $price = $price + $addition;
        $price = round($price ,2);
      }
    }
    return $price;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOrderPrice($product_ids) {
    $total_tax = 0;
    $total = 0;
    $additions = [];
    $total_order = [];
    if($product_ids) {
      foreach ($product_ids as $key => $product_id) {
        if ($product_id != 0) {
          /** @var ProductEntity $product */
          $product = $this->entityTypeManager->getStorage('product_entity')->load($product_id);
          $price = $product->getProductPrice();
          $product_additions = $this->getProductTaxes($product, $price);
          if(count($product_additions)) {
            $additions = array_merge($additions, $product_additions);
          }
          $total = $total + $price;
        }
      }
      foreach ($additions as $addition) {
        $total_tax = $total_tax + $addition;
        $total_tax = round($total_tax ,2);
      }
      $total_order = ['total_tax' => $total_tax, 'total_order' => $total + $total_tax];
    }
    return $total_order;
  }
}
