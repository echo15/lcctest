<?php

namespace Drupal\selectra_test_two\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\selectra_test_two\Service\ProductListManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SelectraForm.
 */
class SelectraForm extends FormBase {

  protected $productListManager;

  /**
   * Object constructor.
   *
   */
  public function __construct(
    ProductListManagerInterface $productListManager
  ) {
    $this->productListManager = $productListManager;
  }

  /**
   * Named constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   DI container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('selectra_test_two.product_list_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'selectra_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $products_options = $this->productListManager->createProductList();

    $form['products'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Products'),
      '#options' => $products_options,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    kint($form_state);
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()
        ->addMessage($key . ': ' . ($key === 'text_format' ? $value['value'] : $value));
    }
  }

}
