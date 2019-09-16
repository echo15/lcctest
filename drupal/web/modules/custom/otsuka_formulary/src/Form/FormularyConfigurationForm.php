<?php

namespace Drupal\otsuka_formulary\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Otsuka_formularyConfigurationForm.
 */
class FormularyConfigurationForm extends ConfigFormBase {
  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(EntityStorageInterface $file_storage, FileUsageInterface $file_usage) {
    $this->fileStorage = $file_storage;
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('file'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otsuka_formulary_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('otsuka_formulary.settings');

    // MMIT Formulary API Settings for Abilify Maintena & Rexulti.
    $form['mmit_general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Formulary Lookup API Settings'),
    ];
    $form['mmit_general']['mmit_formulary_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MMIT Formulary API URL'),
      '#default_value' => $config->get('otsuka_formulary_mmit_formulary_api_url'),
      '#required' => TRUE,
    ];
    $form['mmit_general']['mmit_formulary_authentication_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MMIT Formulary Authentication API URL'),
      '#default_value' => $config->get('otsuka_formulary_mmit_formulary_authentication_url'),
      '#required' => TRUE,
    ];
    $form['mmit_general']['mmit_formulary_authentication_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MMIT Formulary Authentication Username'),
      '#default_value' => $config->get('otsuka_formulary_mmit_formulary_authentication_username'),
      '#required' => TRUE,
    ];
    $form['mmit_general']['mmit_formulary_authentication_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MMIT Formulary Authentication Password'),
      '#default_value' => $config->get('otsuka_formulary_mmit_formulary_authentication_password'),
      '#required' => TRUE,
    ];

    $form['mmit_general']['formulary_api_method'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Formulary API Method'),
      '#default_value' => $config->get('otsuka_formulary_formulary_method'),
      '#required' => TRUE,
    ];

    $form['mmit_general']['formulary_wrapper']['formulary_loading_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Formulary Loading Image'),
      '#upload_location' => 'public://',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif'],
      ],
      '#default_value' => $config->get('formulary_loading_image'),
    ];
    $form['mmit_general']['formulary_loading_image_hidden'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('formulary_loading_image'),
    ];

    // SAMSCA Formulary API Settings.
    $form['samsca'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Samsca-specific Formulary API Settings'),
    ];
    $form['samsca']['formulary_api_drug_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API Drug id'),
      '#default_value' => $config->get('otsuka_formulary_formulary_drug_id'),
      '#required' => TRUE,
    ];
    $form['samsca']['formulary_api_drug_class_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API Drug class id'),
      '#default_value' => $config->get('otsuka_formulary_formulary_drug_class_id'),
      '#required' => TRUE,
    ];
    $form['samsca']['formulary_api_indication_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API Indication id'),
      '#default_value' => $config->get('otsuka_formulary_formulary_indication_id'),
      '#required' => TRUE,
    ];
    $form['samsca']['formulary_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API URL'),
      '#default_value' => $config->get('otsuka_formulary_formulary_api_url'),
      '#required' => TRUE,
    ];
    $form['samsca']['formulary_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API Key'),
      '#default_value' => $config->get('otsuka_formulary_formulary_api_key'),
      '#required' => TRUE,
    ];
    $form['samsca']['custom_notes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Samsca-specific Formulary API Settings for Custom Note field'),
    ];
    $form['samsca']['custom_notes']['formulary_api_product_id_samsca'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API Product id'),
      '#default_value' => $config->get('otsuka_formulary_notes_product_id_samsca'),
      '#required' => FALSE,
    ];
    $form['samsca']['custom_notes']['formulary_api_derivedfieldid_samsca'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Samsca Formulary API DerivedFieldId'),
      '#default_value' => $config->get('otsuka_formulary_notes_derivedfieldid_samsca'),
      '#required' => FALSE,
    ];

    // Abilify Formulary API Settings.
    $form['abilify'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Abilify-specific Formulary API Settings for Custom Note field'),
    ];
    $form['abilify']['formulary_api_product_id_abilifymaintena'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Abilify Formulary API Product id'),
      '#default_value' => $config->get('otsuka_formulary_notes_product_id_abilifymaintena'),
      '#required' => FALSE,
    ];
    $form['abilify']['formulary_api_derivedfieldid_abilifymaintena'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Abilify Formulary API DerivedFieldId'),
      '#default_value' => $config->get('otsuka_formulary_notes_derivedfieldid_abilifymaintena'),
      '#required' => FALSE,
    ];

    // Rexulti Formulary API Settings.
    $form['rexulti'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rexulti-specific Formulary API Settings for Custom Note field'),
    ];
    $form['rexulti']['formulary_api_product_id_rexulti'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rexulti Formulary API Product id'),
      '#default_value' => $config->get('otsuka_formulary_notes_product_id_rexulti'),
      '#required' => FALSE,
    ];
    $form['rexulti']['formulary_api_derivedfieldid_rexulti'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rexulti Formulary API DerivedFieldId'),
      '#default_value' => $config->get('otsuka_formulary_notes_derivedfieldid_rexulti'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('otsuka_formulary.settings');

    $config->set('otsuka_formulary_mmit_formulary_api_url', $form_state->getValue('mmit_formulary_api_url'));
    $config->set('otsuka_formulary_mmit_formulary_authentication_url', $form_state->getValue('mmit_formulary_authentication_url'));
    $config->set('otsuka_formulary_mmit_formulary_authentication_username', $form_state->getValue('mmit_formulary_authentication_username'));
    $config->set('otsuka_formulary_mmit_formulary_authentication_password', $form_state->getValue('mmit_formulary_authentication_password'));
    $config->set('otsuka_formulary_formulary_method', $form_state->getValue('formulary_api_method'));
    // Save Loading icon.
    $formulary_loading_image = $form_state->getValue('formulary_loading_image');
    if (!empty($formulary_loading_image)) {
      $file = $this->fileStorage->load($formulary_loading_image[0]);
      $file->status = FILE_STATUS_PERMANENT;
      $file->save();
      $file_usage = $this->fileUsage;
      $file_usage->add($file, 'otsuka_formulary', 'file', $formulary_loading_image[0]);
    }
    $config->set('formulary_loading_image', $formulary_loading_image);

    // SAMSCA Formulary API Settings.
    $config->set('otsuka_formulary_formulary_drug_id', $form_state->getValue('formulary_api_drug_id'));
    $config->set('otsuka_formulary_formulary_drug_class_id', $form_state->getValue('formulary_api_drug_class_id'));
    $config->set('otsuka_formulary_formulary_indication_id', $form_state->getValue('formulary_api_indication_id'));
    $config->set('otsuka_formulary_formulary_api_url', $form_state->getValue('formulary_api_url'));
    $config->set('otsuka_formulary_formulary_api_key', $form_state->getValue('formulary_api_key'));
    $config->set('otsuka_formulary_notes_product_id_samsca', $form_state->getValue('formulary_api_product_id_samsca'));
    $config->set('otsuka_formulary_notes_derivedfieldid_samsca', $form_state->getValue('formulary_api_derivedfieldid_samsca'));

    // Abilify Formulary API Settings.
    $config->set('otsuka_formulary_notes_product_id_abilifymaintena', $form_state->getValue('formulary_api_product_id_abilifymaintena'));
    $config->set('otsuka_formulary_notes_derivedfieldid_abilifymaintena', $form_state->getValue('formulary_api_derivedfieldid_abilifymaintena'));

    // Rexulti Formulary API Settings.
    $config->set('otsuka_formulary_notes_product_id_rexulti', $form_state->getValue('formulary_api_product_id_rexulti'));
    $config->set('otsuka_formulary_notes_derivedfieldid_rexulti', $form_state->getValue('formulary_api_derivedfieldid_rexulti'));

    $config->save();
    return parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'otsuka_formulary.settings',
    ];
  }

}
