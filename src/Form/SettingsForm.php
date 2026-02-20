<?php

namespace Drupal\ngo_tools_newsletter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ngo_tools_newsletter\Service\EncryptionService;
use Drupal\ngo_tools_newsletter\Service\NgoToolsApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure NGO Tools Newsletter settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\ngo_tools_newsletter\Service\EncryptionService
   */
  protected $encryptionService;

  /**
   * The NGO Tools API service.
   *
   * @var \Drupal\ngo_tools_newsletter\Service\NgoToolsApiService
   */
  protected $apiService;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\ngo_tools_newsletter\Service\EncryptionService $encryption_service
   *   The encryption service.
   * @param \Drupal\ngo_tools_newsletter\Service\NgoToolsApiService $api_service
   *   The API service.
   */
  public function __construct(EncryptionService $encryption_service, NgoToolsApiService $api_service) {
    $this->encryptionService = $encryption_service;
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngo_tools_newsletter.encryption'),
      $container->get('ngo_tools_newsletter.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngo_tools_newsletter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ngo_tools_newsletter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ngo_tools_newsletter.settings');
    $organization_name = $config->get('organization_name');
    $selected_segment = $config->get('segment_id');

    // Validate organization name.
    $is_valid_org = $this->isValidOrganizationName($organization_name);

    $form['api_bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Bearer Token'),
      '#default_value' => '',
      '#description' => $this->t('You can generate your token in the Profile area under the "API Token" section. Enter a value if you want to set a new token.'),
      '#autocomplete_route_name' => FALSE,
      '#attributes' => [
        'autocomplete' => 'off',
        'placeholder' => $this->t('Enter a value if you want to set a new token'),
      ],
    ];

    $form['organization_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization Name'),
      '#default_value' => $organization_name,
      '#description' => $this->t('Your organization name is the first part of the URL. For example, if your URL is examplename.ngo.tools/app/dashboard, the value to enter is examplename.ngo.tools.'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('i.e.: organization.ngo.tools'),
      ],
    ];

    if (!$is_valid_org && !empty($organization_name)) {
      $this->messenger()->addError($this->t("The organisation name can't be empty and must end with .ngo.tools"));
    }

    // Fetch segments if token and org name are configured.
    $segments = [];
    if ($is_valid_org) {
      $segments = $this->apiService->fetchContactSegments();

      if (empty($segments)) {
        $this->messenger()->addWarning($this->t('Could not load segments. Please verify the bearer token and organization name.'));
      }
    }

    if (!empty($segments)) {
      $segment_options = ['' => $this->t('Please select a segment')];
      foreach ($segments as $segment) {
        if (isset($segment['id']) && isset($segment['name'])) {
          $segment_options[$segment['id']] = $segment['name'];
        }
      }

      $form['segment_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Newsletter'),
        '#options' => $segment_options,
        '#default_value' => $selected_segment,
        '#description' => $this->t('Select the newsletter that user filling out the form should be subscribed to.'),
      ];
    }

    $default_confirmation = $this->t('Thank you for signing up. Please check your email and click the confirmation link to complete your subscription.');
    $form['confirmation_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Confirmation Message'),
      '#default_value' => $config->get('confirmation_message') ?: $default_confirmation,
      '#description' => $this->t('The message shown to users after they submit the form. Use [email] to insert the address they entered (e.g. "We sent a confirmation link to [email].").'),
      '#required' => TRUE,
      '#rows' => 4,
      '#attributes' => [
        'placeholder' => (string) $default_confirmation,
      ],
      '#weight' => 100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ngo_tools_newsletter.settings');

    // Handle bearer token encryption.
    $bearer_token = $form_state->getValue('api_bearer_token');
    if (!empty($bearer_token)) {
      $encrypted_token = $this->encryptionService->encrypt($bearer_token);
      $config->set('api_bearer_token', $encrypted_token);
    }

    // Save other settings.
    $config->set('organization_name', $form_state->getValue('organization_name'));
    $config->set('confirmation_message', $form_state->getValue('confirmation_message'));

    if ($form_state->hasValue('segment_id')) {
      $config->set('segment_id', $form_state->getValue('segment_id'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validates if the organization name is in the correct format.
   *
   * @param string $organization_name
   *   The organization name to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  protected function isValidOrganizationName($organization_name) {
    return !empty($organization_name) && preg_match('/.+\.ngo\.tools$/m', $organization_name);
  }

}
