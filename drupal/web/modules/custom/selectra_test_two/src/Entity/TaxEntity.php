<?php

namespace Drupal\selectra_test_two\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Tax entity entity.
 *
 * @ConfigEntityType(
 *   id = "tax_entity",
 *   label = @Translation("Tax entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\selectra_test_two\TaxEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\selectra_test_two\Form\TaxEntityForm",
 *       "edit" = "Drupal\selectra_test_two\Form\TaxEntityForm",
 *       "delete" = "Drupal\selectra_test_two\Form\TaxEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\selectra_test_two\TaxEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tax_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/tax_entity/{tax_entity}",
 *     "add-form" = "/admin/structure/tax_entity/add",
 *     "edit-form" = "/admin/structure/tax_entity/{tax_entity}/edit",
 *     "delete-form" = "/admin/structure/tax_entity/{tax_entity}/delete",
 *     "collection" = "/admin/structure/tax_entity"
 *   }
 * )
 */
class TaxEntity extends ConfigEntityBase implements TaxEntityInterface {

  /**
   * The Tax entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Tax entity label.
   *
   * @var string
   */
  protected $label;

}
