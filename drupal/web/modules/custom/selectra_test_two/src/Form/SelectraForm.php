<?php

namespace Drupal\selectra_test_two\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\selectra_test_two\Service\ProductListManagerInterface;
use Drupal\selectra_test_two\Service\TaxCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Class SelectraForm.
 */
class SelectraForm extends FormBase {

  protected $productListManager;

  protected $taxCalculator;

  protected $messenger;

  /**
   * Object constructor.
   *
   */
  public function __construct(
    ProductListManagerInterface $productListManager,
    TaxCalculatorInterface $taxCalculator,
    Messenger $messenger
  ) {
    $this->productListManager = $productListManager;
    $this->taxCalculator = $taxCalculator;
    $this->messenger = $messenger;

  }

  /**
   * Named constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   DI container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('selectra_test_two.product_list_manager'),
      $container->get('selectra_test_two.tax_calculator'),
      $container->get('messenger')
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
    $product_values = $form_state->getValue('products');
    $taxes = $this->taxCalculator->calculateOrderPrice($product_values);
    $order_price = $this->t('Sales Taxes: @taxes Total: @total', [
      '@taxes' => $taxes['total_tax'],
      '@total' => $taxes['total_order']
    ]);
    $this->messenger->addStatus($order_price);
  }

}
