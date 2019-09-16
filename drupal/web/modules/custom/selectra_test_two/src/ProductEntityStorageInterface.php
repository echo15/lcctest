<?php

namespace Drupal\selectra_test_two;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\selectra_test_two\Entity\ProductEntityInterface;

/**
 * Defines the storage handler class for Product entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Product entity entities.
 *
 * @ingroup selectra_test_two
 */
interface ProductEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Product entity revision IDs for a specific Product entity.
   *
   * @param \Drupal\selectra_test_two\Entity\ProductEntityInterface $entity
   *   The Product entity entity.
   *
   * @return int[]
   *   Product entity revision IDs (in ascending order).
   */
  public function revisionIds(ProductEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Product entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Product entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\selectra_test_two\Entity\ProductEntityInterface $entity
   *   The Product entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ProductEntityInterface $entity);

  /**
   * Unsets the language for all Product entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
