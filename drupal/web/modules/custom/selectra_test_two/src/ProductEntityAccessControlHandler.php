<?php

namespace Drupal\selectra_test_two;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Product entity entity.
 *
 * @see \Drupal\selectra_test_two\Entity\ProductEntity.
 */
class ProductEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\selectra_test_two\Entity\ProductEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished product entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published product entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit product entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete product entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add product entity entities');
  }

}
