<?php

namespace Drupal\otsuka_formulary\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityStorageInterface;

// Use Drupal\taxonomy\Entity\Term;
// use Drupal\Core\Theme\ThemeManagerInterface;
// use Drupal\Core\Form\FormBuilderInterface;.
/**
 * Class InsuranceFormData.
 */
class InsuranceFormData extends FormBase {

  /**
   * The link builder service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A EntityTypeManager object.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManager $entityTypeManager, ClientInterface $httpClient, EntityStorageInterface $file_storage) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
    $this->httpClient = $httpClient;
    $this->fileStorage = $file_storage;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('current_route_match'), $container->get('entity_type.manager'), $container->get('http_client'), $container->get('entity.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'insuranceformdata_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // To get product and user type based on node.
    $config = $this->config('otsuka_formulary.settings');
    $url = $config->get('otsuka_formulary_formulary_api_url');
    $api_key = $config->get('otsuka_formulary_formulary_api_key');
    $method = $config->get('otsuka_formulary_formulary_method');
    $client = $this->httpClient;
    // Get Coverage types from API.
    try {
      $coverage_types = $client->request($method, $url . 'health_plan_types.json?api_key=' . $api_key, [
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $coverage_types_data = json_decode($coverage_types->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }

    $coverage_types_options[0] = 'All (Recommended)';
    if ($coverage_types_data) {
      foreach ($coverage_types_data as $key) {
        $commercial = NULL;
        $commercial = in_array($key->health_plan_type->id, [
          1, 2, 19, 4, 15, 16, 8, 9, 11,
        ]);
        if ($commercial) {
          $coverage_types_options[1] = 'Commercial';
        }
        elseif (in_array($key->health_plan_type->id, [13, 14])) {
          $coverage_types_options[13] = 'Health Exchange';
        }
        elseif (in_array($key->health_plan_type->id, [23, 22, 20, 21])) {
          // $coverage_types_options[13] = 'HIX';.
        }
        elseif (in_array($key->health_plan_type->id, [5, 6, 7, 17, 18])) {
          $coverage_types_options[6] = 'Medicare';
        }
        else {
          $coverage_types_options[$key->health_plan_type->id] = $key->health_plan_type->name;
        }
      }
    }
    // Get states from API.
    try {
      $states = $client->request($method, $url . 'states.json?api_key=' . $api_key, [
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $states_data = json_decode($states->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }

    $states_options = [];
    if (isset($states_data) && $states_data) {
      foreach ($states_data as $key) {
        $states_options[$key->state->id] = $key->state->name;
      }
    }

    // Get brandname from taxanomy & compare with current url.
    try {
      /* Get current path to check which brand the site is in. */
      $node = $this->routeMatch->getParameter('node');
      if (is_object($node) && isset($node->get('field_brand')->target_id)) {
        $content_brand = $node->get('field_brand')->target_id;
      }
      else {
        $config = $this->configFactory->getEditable('otsuka_common.common_settings');
        $content_brand = $config->get('default_brand');
      }

      $all_brands = otsuka_common_get_all_brands();
      foreach ($all_brands as $key => $brand) {
        if ($key == $content_brand) {
          $brand_pi_warning = str_replace(["<br />"], [""], $brand->brand_pi_warning);
          $brand_pi_warning_for_preview = $brand->brand_pi_warning;
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_brand_pi', $e->getMessage());
    }

    $form = $this->commonForm($coverage_types_options, $states_options, $brand_pi_warning, $brand_pi_warning_for_preview);
    return $form;
  }

  /**
   * Construct form Data based on API Information.
   */
  public function commonForm($coverage_types_options, $states_options, $brand_pi_warning = '', $brand_pi_warning_for_preview = '') {
    $config = $this->config('otsuka_formulary.settings');
    $formulary_loading_image = $config->get('formulary_loading_image');
    if ($formulary_loading_image[0] > 0) {
      $file = $this->fileStorage->load($formulary_loading_image[0]);
      if (is_object($file)) {
        $formulary_loading_image = file_create_url($file->getFileUri());
      }
    }
    drupal_get_messages('error');
    $form['coverage_type'] = [
      '#type' => 'select',
      '#options' => $coverage_types_options,
      '#title' => $this->t('Select Coverage Type (Required)'),
      '#default_value' => 'default',
      '#ajax' => [
        'callback' => '::healthplanCallback',
        'wrapper' => 'healthplan-wrapper',
        'method' => 'replaceWith',
      ],
      '#empty_option' => $this->t('Select Coverage Type'),
      '#empty_value' => 'hidden',
    ];
    $form['states_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'states-wrapper'],
    ];
    $form['states_wrapper']['state'] = [
      '#type' => 'select',
      '#options' => $states_options,
      '#title' => $this->t('Select State (Required)'),
      '#default_value' => 'Select State',
      '#ajax' => [
        'callback' => '::healthplanCallback',
        'wrapper' => 'healthplan-wrapper',
        'method' => 'replaceWith',
        'progress' => ['type' => 'fullscreen'],
      ],
      '#empty_option' => $this->t('Select State'),
      '#empty_value' => 'hidden',
      '#prefix' => '<span class="states-valid-message"></span>',
    ];
    $form['healthplan_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'healthplan-wrapper'],
    ];
    $form['healthplan_wrapper']['healthplan'] = [
      '#type' => 'select',
      '#options' => [
        'select' => $this->t('Select Health Plan'),
      ],
      '#title' => $this->t('Select Health Plan (Required)'),
      '#attributes' => [
        'disabled' => 'disabled',
      ],
      '#empty_option' => $this->t('Select Health Plan'),
      '#empty_value' => 'hidden',
    ];
    $form['brand_pi_warning'] = [
      '#type' => 'hidden',
      '#value' => $brand_pi_warning,
      '#attributes' => ['id' => 'brand_pi_warning'],
    ];
    $form['brand_pi_warning_for_preview'] = [
      '#type' => 'hidden',
      '#value' => $brand_pi_warning_for_preview,
      '#attributes' => ['id' => 'brand_pi_warning_for_preview'],
    ];
    $form['#attached']['library'][] = 'otsuka_formulary/otsuka_formulary';
    $form['#attached']['drupalSettings']['data']['otsuka_formulary']['formulary_loading_image'] = $formulary_loading_image;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Look up Formulary'),
      '#attributes' => [
        'class' => ['insurance-select-data'],
      ],
    ];
    $form['insurance_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'insurance-wrapper'],
    ];
    $form['insurance_wrapper']['insurance'] = [
      '#type' => 'item',
      '#markup' => '',
    ];
    return $form;
  }

  /**
   * Function healthplanCallback().
   */
  public function healthplanCallback(array &$form, FormStateInterface &$form_state) {
    $config = $this->config('otsuka_formulary.settings');
    $url = $config->get('otsuka_formulary_formulary_api_url');
    $api_key = $config->get('otsuka_formulary_formulary_api_key');
    $method = $config->get('otsuka_formulary_formulary_method');
    $client = $this->httpClient;

    $states = $form_state->getValue('state');
    if (empty($states)) {
      $response = new AjaxResponse();
      $message = 'Please select the state';
      $response->addCommand(new HtmlCommand('.states-valid-message', $message));
      return $response;
    }
    $coverage_type = $form_state->getValue('coverage_type');
    $coverage_type = !empty($coverage_type) ? '&health_plan_type_id=' . $coverage_type : '';
    try {
      $plan_types = $client->request($method, $url . 'health_plans.json?api_key=' . $api_key . $coverage_type . '&state_id=' . $states, [
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $plan_types_data = json_decode($plan_types->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }
    $plan_types_options = [];

    foreach ($plan_types_data as $key) {
      $plan_types_options[$key->health_plan->displayid] = $key->health_plan->webname;
    }

    $plan_types_options = ['hidden' => $this->t('Type or select a health plan')] + $plan_types_options;

    $form['healthplan_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'healthplan-wrapper'],
    ];
    $form['healthplan_wrapper']['healthplan'] = [
      '#type' => 'select',
      '#options' => $plan_types_options,
      '#title' => $this->t('Select Health Plan (Required)'),
      '#default_value' => 'Type or select a health plan',
      '#attributes' => [
        'id' => ['healthplan-select-data'],
        'placeholder' => $this->t('Select Health Plan'),
      ],
    ];
    $form_state->setRebuild();
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('.states-valid-message', ''));
    return $ajax_response->addCommand(new HtmlCommand('#healthplan-wrapper', $form['healthplan_wrapper']));
  }

  /**
   * Validate().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $states = $form_state->getValue('state');
    if (empty($states)) {
      $form_state->setErrorByName('state', $this->t('Please select the state.'));
    }
  }

  /**
   * Submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
