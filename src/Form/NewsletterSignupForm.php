<?php

namespace Drupal\ngo_tools_newsletter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ngo_tools_newsletter\Service\NgoToolsApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a newsletter signup form.
 */
class NewsletterSignupForm extends FormBase {

  /**
   * The NGO Tools API service.
   *
   * @var \Drupal\ngo_tools_newsletter\Service\NgoToolsApiService
   */
  protected $apiService;

  /**
   * Constructs a NewsletterSignupForm object.
   *
   * @param \Drupal\ngo_tools_newsletter\Service\NgoToolsApiService $api_service
   *   The API service.
   */
  public function __construct(NgoToolsApiService $api_service) {
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngo_tools_newsletter.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngo_tools_newsletter_signup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'ngo-tools-newsletter-form';
    $form['#theme'] = 'ngo_tools_newsletter_form';

    // Honeypot field for spam protection.
    $form['hp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Leave this field empty'),
      '#attributes' => [
        'style' => 'position:absolute;left:-99999px;top:auto;width:1px;height:1px;overflow:hidden;',
        'tabindex' => '-1',
        'autocomplete' => 'off',
      ],
    ];

    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => $this->t('First Name'),
      ],
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => $this->t('Last Name'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Email'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#attributes' => [
        'class' => ['ngo-tools-newsletter-form-button'],
      ],
    ];

    $form['#attached']['library'][] = 'ngo_tools_newsletter/newsletter_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check honeypot.
    if (!empty($form_state->getValue('hp'))) {
      $form_state->setErrorByName('hp', $this->t('Spam detected!'));
      return;
    }

    // Validate email format.
    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'firstName' => $form_state->getValue('first_name'),
      'lastName' => $form_state->getValue('last_name'),
      'email' => $form_state->getValue('email'),
    ];

    $result = $this->apiService->subscribeContact($data);

    if ($result['success']) {
      $this->messenger()->addStatus($result['message']);
    }
    else {
      $this->messenger()->addError($result['message']);
    }
  }

}
