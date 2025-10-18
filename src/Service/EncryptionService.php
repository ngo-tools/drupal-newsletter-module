<?php

namespace Drupal\ngo_tools_newsletter\Service;

use Drupal\Core\Site\Settings;

/**
 * Service for encrypting and decrypting sensitive data.
 */
class EncryptionService {

  /**
   * The encryption key.
   *
   * @var string
   */
  protected $key;

  /**
   * Constructs an EncryptionService object.
   */
  public function __construct() {
    // Use Drupal's hash salt as encryption key.
    $this->key = Settings::getHashSalt();
  }

  /**
   * Encrypts data using AES-256-CBC.
   *
   * @param string $data
   *   The data to encrypt.
   *
   * @return string
   *   The encrypted data, base64 encoded.
   */
  public function encrypt(string $data): string {
    if (empty($data)) {
      return '';
    }

    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, $cipher, $this->key, OPENSSL_RAW_DATA, $iv);

    // Store IV with ciphertext for decryption.
    return base64_encode($iv . $ciphertext_raw);
  }

  /**
   * Decrypts data that was encrypted with encrypt().
   *
   * @param string $data
   *   The encrypted data, base64 encoded.
   *
   * @return string
   *   The decrypted data.
   */
  public function decrypt(string $data): string {
    if (empty($data)) {
      return '';
    }

    $cipher = "AES-256-CBC";
    $c = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $ciphertext_raw = substr($c, $ivlen);
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $this->key, OPENSSL_RAW_DATA, $iv);

    return $original_plaintext ?: '';
  }

}
