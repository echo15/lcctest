<?php

namespace Drupal\selectra_test_two\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TaxEntityForm.
 */
class TaxEntityForm extends EntityForm {

  protected $entityFieldManager;

  /**
   * Object constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Named constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   DI container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $tax_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $tax_entity->label(),
      '#description' => $this->t("Label for the Tax entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $tax_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\selectra_test_two\Entity\TaxEntity::load',
      ],
      '#disabled' => !$tax_entity->isNew(),
    ];

    $form['percentage'] = array(
      '#type' => 'number',
      '#title' => $this->t('Tax Percentage'),
      '#maxlength' => 255,
      '#default_value' => $tax_entity->get('percentage'),
      '#description' => $this->t("Tax Percentage value"),
      '#required' => TRUE,
    );

    $fields = $this->entityFieldManager->getFieldStorageDefinitions('product_entity');
    $options = options_allowed_values($fields['type']);
    $form['product_types'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product Types'),
      '#options' => $options,
      '#default_value' => $tax_entity->get('product_types'),
      '#description' => $this->t("Tax Percentage value"),
      '#required' => TRUE,
      '#multiple' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tax_entity = $this->entity;
    $status = $tax_entity->save();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Tax entity.', [
          '%label' => $tax_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Tax entity.', [
          '%label' => $tax_entity->label(),
        ]));
    }
//    $form_state->setRedirectUrl($tax_entity->toUrl('collection'));
  }

}
