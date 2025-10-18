<?php

namespace Drupal\ngo_tools_newsletter\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for interacting with the NGO Tools API.
 */
class NgoToolsApiService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The encryption service.
   *
   * @var \Drupal\ngo_tools_newsletter\Service\EncryptionService
   */
  protected $encryptionService;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a NgoToolsApiService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client factory.
   * @param \Drupal\ngo_tools_newsletter\Service\EncryptionService $encryption_service
   *   The encryption service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientFactory $http_client_factory,
    EncryptionService $encryption_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->httpClientFactory = $http_client_factory;
    $this->encryptionService = $encryption_service;
    $this->logger = $logger_factory->get('ngo_tools_newsletter');
  }

  /**
   * Gets the decrypted bearer token.
   *
   * @return string
   *   The decrypted bearer token.
   */
  protected function getBearerToken(): string {
    $config = $this->configFactory->get('ngo_tools_newsletter.settings');
    $encrypted_token = $config->get('api_bearer_token');
    return $encrypted_token ? $this->encryptionService->decrypt($encrypted_token) : '';
  }

  /**
   * Gets the organization name from configuration.
   *
   * @return string
   *   The organization name.
   */
  protected function getOrganizationName(): string {
    $config = $this->configFactory->get('ngo_tools_newsletter.settings');
    return $config->get('organization_name') ?: '';
  }

  /**
   * Gets the segment ID from configuration.
   *
   * @return string
   *   The segment ID.
   */
  protected function getSegmentId(): string {
    $config = $this->configFactory->get('ngo_tools_newsletter.settings');
    return $config->get('segment_id') ?: '';
  }

  /**
   * Builds the API endpoint URL for contact segments.
   *
   * @return string
   *   The API endpoint URL.
   */
  protected function getContactSegmentsUrl(): string {
    $organization_name = $this->getOrganizationName();
    return "https://{$organization_name}/api/v2/contact-segments";
  }

  /**
   * Builds the API endpoint URL for subscribing to a segment.
   *
   * @param string $segment_id
   *   The segment ID.
   *
   * @return string
   *   The API endpoint URL.
   */
  protected function getSubscribeUrl(string $segment_id): string {
    $organization_name = $this->getOrganizationName();
    return "https://{$organization_name}/api/v2/contact-segments/{$segment_id}/subscribe";
  }

  /**
   * Gets default request headers for API calls.
   *
   * @return array
   *   Array of headers.
   */
  protected function getDefaultHeaders(): array {
    $bearer_token = $this->getBearerToken();
    return [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
      'Authorization' => $bearer_token ? 'Bearer ' . $bearer_token : '',
    ];
  }

  /**
   * Fetches available contact segments from the API.
   *
   * @return array
   *   Array of segments, or empty array on failure.
   */
  public function fetchContactSegments(): array {
    $organization_name = $this->getOrganizationName();
    $bearer_token = $this->getBearerToken();

    if (empty($bearer_token) || empty($organization_name)) {
      return [];
    }

    $url = $this->getContactSegmentsUrl();
    $client = $this->httpClientFactory->fromOptions([
      'verify' => FALSE,
    ]);

    try {
      $response = $client->get($url, [
        'headers' => $this->getDefaultHeaders(),
        'timeout' => 15,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      if (isset($data['data']) && is_array($data['data'])) {
        return $data['data'];
      }

      return [];
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to fetch contact segments: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Subscribes a contact to the newsletter.
   *
   * @param array $data
   *   Array containing firstName, lastName, and email.
   *
   * @return array
   *   Array with 'success' (bool) and 'message' (string) keys.
   */
  public function subscribeContact(array $data): array {
    $segment_id = $this->getSegmentId();
    $bearer_token = $this->getBearerToken();

    if (empty($segment_id)) {
      return [
        'success' => FALSE,
        'message' => $this->t('Newsletter segment is not configured.'),
      ];
    }

    if (empty($bearer_token)) {
      return [
        'success' => FALSE,
        'message' => $this->t('API bearer token is not configured.'),
      ];
    }

    $url = $this->getSubscribeUrl($segment_id);
    $client = $this->httpClientFactory->fromOptions([
      'verify' => FALSE,
    ]);

    try {
      $response = $client->post($url, [
        'headers' => $this->getDefaultHeaders(),
        'json' => $data,
        'timeout' => 15,
      ]);

      $status_code = $response->getStatusCode();

      if (in_array($status_code, [200, 201])) {
        return [
          'success' => TRUE,
          'message' => $this->t('Thank you for subscribing!'),
        ];
      }

      return [
        'success' => FALSE,
        'message' => $this->t('Failed to subscribe: Unknown error'),
      ];
    }
    catch (RequestException $e) {
      $status_code = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
      $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';

      // Check if token is expired (redirect to login).
      if (stripos($body, 'login') !== FALSE) {
        return [
          'success' => FALSE,
          'message' => $this->t('Failed to subscribe: The token seems to be expired'),
        ];
      }

      if ($status_code === 404) {
        return [
          'success' => FALSE,
          'message' => $this->t('Failed to subscribe: Endpoint not found'),
        ];
      }

      $this->logger->error('Failed to subscribe contact: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'message' => $this->t('Failed to subscribe: @error', ['@error' => $e->getMessage()]),
      ];
    }
  }

  /**
   * Translates a string.
   *
   * @param string $string
   *   The string to translate.
   * @param array $args
   *   An associative array of replacements.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated string.
   */
  protected function t($string, array $args = []) {
    return t($string, $args);
  }

}
