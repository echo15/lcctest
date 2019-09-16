<?php

namespace Drupal\selectra_test_two\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Product entity entities.
 */
class ProductEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
