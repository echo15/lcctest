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

/**
 * Class MMITInsuranceFormData.
 */
class MMITInsuranceFormData extends FormBase {

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
    return 'mmitinsuranceformdata_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get mmit configurations url, authentication url, username/password.
    $config = $this->config('otsuka_formulary.settings');
    $mmit_url = $config->get('otsuka_formulary_mmit_formulary_api_url');
    $mmit_authentication_url = $config->get('otsuka_formulary_mmit_formulary_authentication_url');
    $mmit_username = $config->get('otsuka_formulary_mmit_formulary_authentication_username');
    $mmit_password = $config->get('otsuka_formulary_mmit_formulary_authentication_password');
    $method = $config->get('otsuka_formulary_formulary_method');

    $client = $this->httpClient;
    try {
      // Get access token from MMIT API.
      $mmit_auth_headers = get_mmit_authentication_headers($client, $mmit_authentication_url, $mmit_username, $mmit_password);

      // Get Channel types from MMIT API.
      $channel_types = $client->request($method, $mmit_url . '/Plans/Channels', [
        'headers' => $mmit_auth_headers,
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $channel_types_response = json_decode($channel_types->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_channeltypesresponse', $e->getMessage());
    }
    $channel_types_options[0] = 'All (Recommended)';
    if ($channel_types_response) {
      foreach ($channel_types_response->Channels as $channel_types_value) {
        $channel_types_options[$channel_types_value->ChannelId] = $channel_types_value->Name;
      }
    }

    // Get states from MMIT API.
    try {
      $mmit_states = $client->request($method, $mmit_url . '/Geographic/States', [
        'headers' => $mmit_auth_headers,
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $mmit_states_response = json_decode($mmit_states->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_stateresponse', $e->getMessage());
    }
    $mmit_states_options = [];
    $state_ex_list = get_states_excluded();
    if (isset($mmit_states_response) && $mmit_states_response) {
      foreach ($mmit_states_response->States as $mmit_states_value) {
        if (!array_key_exists($mmit_states_value->StateId, $state_ex_list)) {
          $mmit_states_options[$mmit_states_value->StateId] = $mmit_states_value->Name;
        }
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
          $brand_name = $brand->name;
          $brand_pi_warning = str_replace(["<br />"], [""], $brand->brand_pi_warning);
          $brand_pi_warning_for_preview = $brand->brand_pi_warning;
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_brandname', $e->getMessage());
    }
    $form = $this->commonForm($channel_types_options, $mmit_states_options, $brand_name, $brand_pi_warning, $brand_pi_warning_for_preview);
    return $form;
  }

  /**
   * Construct form Data based on API Information.
   */
  public function commonForm($channel_types_options, $mmit_states_options, $brand_name, $brand_pi_warning = '', $brand_pi_warning_for_preview = '') {
    $config = $this->config('otsuka_formulary.settings');
    $formulary_loading_image = $config->get('formulary_loading_image');
    if ($formulary_loading_image[0] > 0) {
      $file = $this->fileStorage->load($formulary_loading_image[0]);
      if (is_object($file)) {
        $formulary_loading_image = file_create_url($file->getFileUri());
      }
    }
    drupal_get_messages('error');
    $form['mmit_channel_type'] = [
      '#type' => 'select',
      '#options' => $channel_types_options,
      '#title' => $this->t('Select Coverage Type (Required)'),
      '#ajax' => [
        'callback' => '::mmitHealthPlanCallback',
        'wrapper' => 'healthplan-wrapper',
        'method' => 'replaceWith',
      ],
      '#attributes' => [
        'class' => ['mmitinsurance-coverage-type'],
      ],
      '#empty_option' => $this->t('Select Coverage Type'),
      '#empty_value' => 'hidden',
    ];
    $form['states_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'states-wrapper'],
    ];
    asort($mmit_states_options);
    $form['states_wrapper']['mmitstate'] = [
      '#type' => 'select',
      '#options' => $mmit_states_options,
      '#title' => $this->t('Select State (Required)'),
      '#ajax' => [
        'callback' => '::mmitHealthPlanCallback',
        'wrapper' => 'healthplan-wrapper',
        'method' => 'replaceWith',
        'progress' => ['type' => 'fullscreen'],
      ],
      '#prefix' => '<span class="states-valid-message"></span>',
      '#empty_option' => $this->t('Select State'),
      '#empty_value' => 'hidden',

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
    $form['brand_name'] = [
      '#type' => 'hidden',
      '#value' => $brand_name,
      '#attributes' => ['id' => 'brand_name'],
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
        'class' => ['mmitinsurance-select-data'],
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
   * Function mmitHealthPlanCallback().
   */
  public function mmitHealthPlanCallback(array &$form, FormStateInterface &$form_state) {
    $config = $this->config('otsuka_formulary.settings');
    $mmit_url = $config->get('otsuka_formulary_mmit_formulary_api_url');
    $mmit_authentication_url = $config->get('otsuka_formulary_mmit_formulary_authentication_url');
    $mmit_username = $config->get('otsuka_formulary_mmit_formulary_authentication_username');
    $mmit_password = $config->get('otsuka_formulary_mmit_formulary_authentication_password');
    $method = $config->get('otsuka_formulary_formulary_method');

    $client = $this->httpClient;

    $states = $form_state->getValue('mmitstate');
    if (empty($states)) {
      $response = new AjaxResponse();
      $message = 'Please select the state';
      $response->addCommand(new HtmlCommand('.states-valid-message', $message));
      return $response;
    }
    $channel_type = $form_state->getValue('mmit_channel_type');
    try {
      // Get Plan types from MMIT API by passing channelid and stateid.
      // Get access token from MMIT API.
      $mmit_auth_headers = get_mmit_authentication_headers($client, $mmit_authentication_url, $mmit_username, $mmit_password);
      if (empty($channel_type)) {
        $mmit_plan_types = $client->request($method, $mmit_url . '/Plans', [
          'headers' => $mmit_auth_headers,
          'query' => ['StateId' => $states],
          'http_errors' => FALSE,
          'verify' => FALSE,
        ]);
      }
      else {
        $mmit_plan_types = $client->request($method, $mmit_url . '/Plans', [
          'headers' => $mmit_auth_headers,
          'query' => [
            'ChannelId' => $channel_type,
            'StateId' => $states,
          ],
          'http_errors' => FALSE,
          'verify' => FALSE,
        ]);
      }
      $mmit_plan_types_response = json_decode($mmit_plan_types->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_plantypesresponse', $e->getMessage());
    }
    $mmit_plan_types_options = [];

    foreach ($mmit_plan_types_response->Plans as $mmit_plan_types_value) {
      $mmit_plan_types_options[$mmit_plan_types_value->PlanId] = $mmit_plan_types_value->Name;
    }
    asort($mmit_plan_types_options);

    $mmit_plan_types_options = ['hidden' => $this->t('Type or select a health plan')] + $mmit_plan_types_options;

    $form['healthplan_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'healthplan-wrapper'],
    ];
    $form['healthplan_wrapper']['healthplan'] = [
      '#type' => 'select',
      '#options' => $mmit_plan_types_options,
      '#title' => $this->t('Select Health Plan (Required)'),
      '#default_value' => 'Type or select a health plan',
      '#attributes' => [
        'id' => ['mmithealthplan-select-data'],
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
