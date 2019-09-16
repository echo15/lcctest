<?php

namespace Drupal\otsuka_formulary\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Url;

/**
 * AssureAPIController.
 */
class FormularyAPIController extends ControllerBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * Class constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Guzzle HTTP client.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Http\ClientFactory $clientFactory
   *   A ClientFactory object.
   */
  public function __construct(ClientInterface $httpClient, RequestStack $request_stack, ModuleHandlerInterface $module_handler, RendererInterface $renderer, FormBuilderInterface $form_builder, ClientFactory $clientFactory) {
    $this->httpClient = $httpClient;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
    $this->clientFactory = $clientFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('http_client'), $container->get('request_stack'), $container->get('module_handler'), $container->get('renderer'), $container->get('form_builder'), $container->get('http_client_factory')
    );
  }

  /**
   * Function insurancedata().
   */
  public function insurancedata(Request $request) {
    $config = $this->config('otsuka_formulary.settings');
    $url = $config->get('otsuka_formulary_formulary_api_url');
    $api_key = $config->get('otsuka_formulary_formulary_api_key');
    $drug_id = $config->get('otsuka_formulary_formulary_drug_id');
    $drug_class_id = $config->get('otsuka_formulary_formulary_drug_class_id');
    $method = $config->get('otsuka_formulary_formulary_method');
    $requestdata = json_decode($this->requestStack->getCurrentRequest()->getContent());
    $health_plan = $requestdata->healthPlan;

    $clientFactory = $this->clientFactory;
    $cf_client = $clientFactory->fromOptions(['verify' => FALSE]);

    try {
      $samsca_response = $cf_client->{$method}($url . 'formularies.json?api_key=' . $api_key . '&drug_id=' . $drug_id . '&health_plan_list=' . $health_plan, ['http_errors' => FALSE]);
      $samsca_status = json_decode($samsca_response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }
    try {
      $faform_response = $cf_client->{$method}($url . 'pa_forms.json?api_key=' . $api_key . '&drug_id=' . $drug_id . '&drug_class_id=' . $drug_class_id . '&health_plan_id=' . $health_plan, ['http_errors' => FALSE]);
      $faform = json_decode($faform_response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }
    try {
      $health_plan_response = $cf_client->{$method}($url . 'health_plans/' . $health_plan . '.json?api_key=' . $api_key, ['http_errors' => FALSE]);
      $health_plan_response = json_decode($health_plan_response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }
    try {
      $healthplan_contact_response = $cf_client->{$method}($url . 'providers/' . $health_plan_response->health_plan->provider->id . '.json?api_key=' . $api_key, ['http_errors' => FALSE]);
      $healthplan_contact_response = json_decode($healthplan_contact_response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }

    // Below code block has been commented since we are not using restriction
    // criteria at the moment. Also the response time of this api is not
    // matching the performance requirement SLA.
    /*
    try {
    $requirement_response = $cf_client->{$method}($url . 'restrictions.json
    ?api_key=' . $api_key . '&drug_id=' . $drug_id . '&indication_id='.
    $indication_id . '&health_plan_id=' . $health_plan,
    ['http_errors' => FALSE]);
    $requirement_response = json_decode($requirement_response->getBody());
    }
    catch (RequestException $e) {
    watchdog_exception('otsuka_formulary', $e->getMessage());
    }
    if ($requirement_response) {
    foreach ($requirement_response as $key) {
    $req[$key->plan_product_restriction->restriction_code][]
    = $key->plan_product_restriction->criteria_name;
    }
    }
    $req_data = NULL;
    if ($req) {
    foreach ($req as $key => $key_data) {
    $req_data .= $key . ': ';
    foreach ($key_data as $value) {
    $req_data .= $value . "\n";
    }
    }
    } */

    try {
      $states_response = $cf_client->{$method}($url . 'states/' . $requestdata->state . '.json?api_key=' . $api_key, ['http_errors' => FALSE]);
      $states_response = json_decode($states_response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_formulary', $e->getMessage());
    }

    $data = $email_data = NULL;
    $data .= '<div class="formulary-results">';
    $email_data .= '<div class="formulary-results-email-data hidden">';
    if (is_object($states_response)) {
      $data .= '<div class="form-group">
                            <div class="row">

                                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10">
                                    <label for="state" class="field-label">State:</label> <span class="state-val formulary-val"> ' . $states_response->state->name . '</span> </div>
                            </div>
                        </div>';
      $email_data .= $states_response->state->name . '+++++';
    }
    if (is_object($health_plan_response)) {
      $data .= '<div class="form-group">
                            <div class="row">

                                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10">
                                    <label for="health-plan" class="field-label">Health Plan:</label>
                                    <span class="health-plan-val formulary-val"> ' . $health_plan_response->health_plan->webname . '</span></div>
                            </div>
                        </div>';
      $email_data .= $health_plan_response->health_plan->webname . '+++++';
    }
    if (is_array($samsca_status)) {
      $data .= '<div class="form-group">
                            <div class="row">

                                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10">
                                    <label for="rexulti" brand-name="SAMSCA" class="brand-name formulary-value field-label">SAMSCA Status:</label>
                                    <span class="rexulti-val formulary-status formulary-val">' . $samsca_status[0]->drug_formulary->tier_name . '</span></div>
                            </div>
                        </div>';
      $email_data .= 'SAMSCA+++++' . $samsca_status[0]->drug_formulary->tier_name . '+++++';
    }
    if (is_object($healthplan_contact_response)) {
      $phone_num = preg_replace('/\d{3}/', '$0-', str_replace('.', NULL, trim($healthplan_contact_response->provider->phone_number)), 2);
      if (!empty($phone_num)) {
        $phone_num = '1-' . $phone_num;
      }
      $data .= ' <div class="form-group">
                            <div class="row">

                                <div class="col-lg-10 col-md-11 col-sm-11 col-xs-10">
                                    <label for="health-plan-contact" class="field-label">Health Plan Contact: </label>
                                    <span class="contact-val formulary-val"><a href="tel:(' . $phone_num . ')">' . $phone_num . '</a></span></div>
                            </div>
                        </div>';
      $email_data .= $phone_num . '+++++';
    }

    $data .= '</div>';
    $email_data .= '</div>';
    $form = $this->formBuilder->getForm('Drupal\otsuka_email_form\Form\PopupModalForm', NULL, 'formulary');

    $data .= '<div class="formulary-widgets"><div class="row email-widget">

                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        ' . $this->renderer->render($form) . '
                            </div>
                        </div>';
    if (is_array($faform)) {
      $module_path = $this->moduleHandler->getModule('otsuka_formulary')->getPath();
      $link_title_download = [
        '#theme' => 'image',
        '#uri' => $module_path . '/images/download1.png',
        '#alt' => $this->t('download'),
        '#title' => $this->t('download'),
      ];
      $image_path = render($link_title_download);
      $data .= '<div class="formulary-widgets"> <div class="row download-widget">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><a class="force-download" href="' . $faform[0]->pa_form->url . '" target="_blank" >' . $image_path . 'DOWNLOAD</a></div>
                        </div>
                    </div>';
    }
    return new JsonResponse($data . $email_data);
  }

  /**
   * Function formulary_module_productNotesParameterLookup().
   */
  private function formularyModuleProductNotesParameterLookup($product_name) {
    $config = $this->config('otsuka_formulary.settings');
    switch (strtolower($product_name)) {
      case "rexulti":
        $product_id = $config->get('otsuka_formulary_notes_product_id_rexulti') ? $config->get('otsuka_formulary_notes_product_id_rexulti') : '179199';
        $derived_field_id = $config->get('otsuka_formulary_notes_derivedfieldid_rexulti') ? $config->get('otsuka_formulary_notes_derivedfieldid_rexulti') : '915';
        break;

      case "abilify maintena":
        $product_id = $config->get('otsuka_formulary_notes_product_id_abilifymaintena') ? $config->get('otsuka_formulary_notes_product_id_abilifymaintena') : '168250';
        $derived_field_id = $config->get('otsuka_formulary_notes_derivedfieldid_abilifymaintena') ? $config->get('otsuka_formulary_notes_derivedfieldid_abilifymaintena') : '939';
        break;

      // Samsca.
      default:
        $product_id = $config->get('otsuka_formulary_notes_product_id_samsca');
        $derived_field_id = $config->get('otsuka_formulary_notes_derivedfieldid_samsca');
        break;
    }
    $product_info = [
      "productID" => $product_id,
      "derivedFieldID" => $derived_field_id,
    ];
    return $product_info;
  }

  /**
   * Function mmitinsurancedata().
   */
  public function mmitinsurancedata(Request $request) {
    $config = $this->config('otsuka_formulary.settings');
    $mmit_url = $config->get('otsuka_formulary_mmit_formulary_api_url');
    $mmit_authentication_url = $config->get('otsuka_formulary_mmit_formulary_authentication_url');
    $mmit_username = $config->get('otsuka_formulary_mmit_formulary_authentication_username');
    $mmit_password = $config->get('otsuka_formulary_mmit_formulary_authentication_password');
    $method = $config->get('otsuka_formulary_formulary_method');
    $derived_field_id = $config->get('derived_field_id');
    $product_id = NULL;
    $formulary_id = NULL;

    $client = $this->httpClient;
    $requestdata = json_decode($this->requestStack->getCurrentRequest()->getContent());
    $coverage_name = $requestdata->mmitCoverageTypeName;
    $health_planid = $requestdata->mmitHealthPlanID;
    $health_planname = $requestdata->mmitHealthPlanName;
    $state_name = $requestdata->mmitStateName;
    $product_name = $requestdata->mmitBrandName;
    try {
      // Get access token from MMIT API.
      $mmit_auth_headers = get_mmit_authentication_headers($client, $mmit_authentication_url, $mmit_username, $mmit_password);

      // Get productid for product from backend config.
      $product_info = $this->formularyModuleProductNotesParameterLookup($product_name);
      $product_id = $product_info['productID'];

      // Get coverage for product and plan from MMIT API.
      $coverage_request = $client->request($method, $mmit_url . '/Coverage/Plan', [
        'headers' => $mmit_auth_headers,
        'query' => ['ProductId' => $product_id, 'PlanId' => $health_planid],
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);

      $coverage_response = json_decode($coverage_request->getBody());
      foreach ($coverage_response->PlanCoverages as $coverage_values) {
        /* For State & Managed Medicaid */
        $formulary_tierid = $coverage_values->FormularyTierId;
        /* For other coverage types */
        $druglist_tierid = $coverage_values->DrugListTierId;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_coverageresponse', $e->getMessage());
    }

    try {
      /* To get all Formulary Tiers for State & Managed Medicaid coverage types to display brand status  */
      $formularytiers_request = $client->request($method, $mmit_url . '/Coverage/FormularyTiers', [
        'headers' => $mmit_auth_headers,
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $formularytiers_options = [];
      $formularytiers_response = json_decode($formularytiers_request->getBody());
      foreach ($formularytiers_response->FormularyTiers as $formularytiers_value) {
        $formularytiers_options[$formularytiers_value->FormularyTierId] = $formularytiers_value->Name;
        $formulary_tiers = array_search($formulary_tierid, array_flip($formularytiers_options));
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_coverageresponse', $e->getMessage());
    }

    try {
      /* To get all Druglist Tiers for other coverage types to display brand status  */
      $druglisttiers_request = $client->request($method, $mmit_url . '/Coverage/DrugListTiers', [
        'headers' => $mmit_auth_headers,
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $druglisttiers_options = [];
      $druglisttiers_response = json_decode($druglisttiers_request->getBody());
      foreach ($druglisttiers_response->DrugListTiers as $druglisttiers_value) {
        $druglisttiers_options[$druglisttiers_value->DrugListTierId] = $druglisttiers_value->Name;
        $druglist_tiers = array_search($druglist_tierid, array_flip($druglisttiers_options));
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_coverageresponse', $e->getMessage());
    }

    try {
      /* To Get Phonenumber from Plan  */
      $mmit_phonenumber_request = $client->request($method, $mmit_url . '/Plans', [
        'headers' => $mmit_auth_headers,
        'query' => ['PlanId' => $health_planid],
        'http_errors' => FALSE,
        'verify' => FALSE,
      ]);
      $mmit_phonenumber_response = json_decode($mmit_phonenumber_request->getBody());
      foreach ($mmit_phonenumber_response->Plans as $plan) {
        $mmit_phonenumber = isset($plan->Address->Phone)
          ? otsuka_common_format_text($plan->Address->Phone, '(000) 000-0000')
          : '';
        $formulary_id = $plan->FormularyId;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_coverageresponse', $e->getMessage());
    }

    // Set empty product note field.
    $product_note = "";
    try {
      /* Get specific params for product_id and derived_field_id from module config to query custom notes, using dynamic formulary ID. */
      $product_info = $this->formularyModuleProductNotesParameterLookup($product_name);
      $product_id = $product_info['productID'];
      $derived_field_id = $product_info['derivedFieldID'];

      if (!empty($derived_field_id)) {

        $derivedFieldAPIurl = $mmit_url . 'DerivedField';

        $derivedField_request = $client->request($method, $derivedFieldAPIurl, [
          'headers' => $mmit_auth_headers,
          'query' => [
            'ProductId' => $product_id,
            'FormularyId' => $formulary_id,
            'DerivedFieldId' => $derived_field_id,
          ],
          'http_errors' => FALSE,
          'verify' => FALSE,
        ]);

        $derivedField_response = json_decode($derivedField_request->getBody());
        foreach ($derivedField_response->DerivedFields as $derivedField_value) {
          $product_note .= $derivedField_value->Value;
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('otsuka_mmitformulary_coverageresponse', $e->getMessage());
    }

    $components = [];
    $email_data = [];

    if (!empty($state_name)) {
      $components[] = [
        '#id' => 'state',
        '#label' => $this->t('State'),
        '#value' => $state_name,
      ];
      $email_data[] = $state_name;
    }
    if (!empty($health_planname)) {
      $components[] = [
        '#id' => 'health-plan',
        '#label' => $this->t('Health Plan'),
        '#value' => $health_planname,
      ];
      $email_data[] = $health_planname;
    }
    if (!empty($coverage_name)) {
      $value = $coverage_name == 'State Medicaid' || $coverage_name == 'Managed Medicaid' ? $formulary_tiers : $druglist_tiers;
      $components[] = [
        '#id' => 'rexulti',
        '#label' => ucwords($product_name),
        '#value' => $value,
      ];
      $email_data[] = ucwords($product_name);
      $email_data[] = $value;
    }
    if (!empty($mmit_phonenumber)) {
      $phone_num = '1-' . preg_replace('/\d{3}/', '$0-', str_replace('.', NULL, trim($mmit_phonenumber)), 2);
      $url = Url::fromUri("tel:($phone_num)");
      $components[] = [
        '#id' => 'contact',
        '#label' => $this->t('Health Plan Contact'),
        '#value' => $this->getLinkGenerator()->generate($phone_num, $url),
      ];
      $email_data[] = $phone_num;
    }
    if (!empty($product_note)) {
      $components[] = [
        '#id' => 'notes',
        '#label' => $this->t('Additional Restrictions (If applicable)'),
        '#value' => $product_note,
      ];
      $plain_text_notes = str_replace("<BR>", "<br>", $product_note);
      $plain_text_notes = strip_tags($plain_text_notes, "<sup><br>");
      $email_data[] = $plain_text_notes;
    }

    $build = [
      '#theme' => 'otsuka_formulary_result',
      '#components' => $components,
      '#form' => $this->formBuilder->getForm('Drupal\otsuka_email_form\Form\PopupModalForm', NULL, 'formulary'),
      '#mailstring' => implode('+++++', $email_data),
    ];

    return new JsonResponse(render($build));
  }

}
