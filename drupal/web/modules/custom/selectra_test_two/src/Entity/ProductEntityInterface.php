<?php

namespace Drupal\selectra_test_two\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Product entity entities.
 *
 * @ingroup selectra_test_two
 */
interface ProductEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Product entity name.
   *
   * @return string
   *   Name of the Product entity.
   */
  public function getName();

  /**
   * Sets the Product entity name.
   *
   * @param string $name
   *   The Product entity name.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setName($name);

  /**
   * Gets the Product entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Product entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Product entity creation timestamp.
   *
   * @param int $timestamp
   *   The Product entity creation timestamp.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Product entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Product entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Product entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Product entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the Product Imported status.
   *
   * @return bool
   *   TRUE if the Product is imported.
   */
  public function isImported();

  /**
   * Gets the Product price.
   *
   * @return float
   */
  public function getProductPrice();

  /**
   * Set Product price.
   *
   * @param float $price
   *   Product price.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setPrice($price);
  /**
   * Get Product type.
   *
   * @return string
   */
  public function getProductType();

  /**
   * Set Product imported status.
   *
   * @param boolean $imported
   *   Product imported status.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setImported($imported);
  /**
   * Set Product type.
   *
   * @param string $type
   *   Product type.
   *
   * @return \Drupal\selectra_test_two\Entity\ProductEntityInterface
   *   The called Product entity entity.
   */
  public function setProductType($type);

}
