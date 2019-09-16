<?php

namespace Drupal\selectra_test_two\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\selectra_test_two\Entity\ProductEntity;

/**
 * Class ProductListManager.
 */
class ProductListManager implements ProductListManagerInterface {

  protected $entityTypeManager;

  protected $stringTranslation;

  protected $taxCalculator;

  /**
   * Object constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TranslationInterface $stringTranslation,
    TaxCalculatorInterface $taxCalculator
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->stringTranslation = $stringTranslation;
    $this->taxCalculator = $taxCalculator;
  }

  /**
   * {@inheritdoc}
   */
  public function createProductList() {
    $products_options = [];
    $products = $this->entityTypeManager->getStorage('product_entity')->loadMultiple();
    if ($products && is_array($products)) {
      /** @var ProductEntity $product */
      foreach ($products as $product) {
        $id = $product->id();
        $title = $product->getName();
        $price = $this->taxCalculator->calculateProductFullPrice($id);
        $products_options[$id] = $title . ' ' . $price;
      }
    }
    return $products_options;
  }

}
