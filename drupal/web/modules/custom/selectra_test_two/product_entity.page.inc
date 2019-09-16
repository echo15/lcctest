<?php

/**
 * @file
 * Contains product_entity.page.inc.
 *
 * Page callback for Product entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Product entity templates.
 *
 * Default template: product_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_product_entity(array &$variables) {
  // Fetch ProductEntity Entity Object.
  $product_entity = $variables['elements']['#product_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
