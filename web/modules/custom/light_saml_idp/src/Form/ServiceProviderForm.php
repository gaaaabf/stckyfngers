<?php

namespace Drupal\light_saml_idp\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\light_saml_idp\Entity\ServiceProviderInterface;
use LightSaml\Error\LightSamlException;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use LightSaml\Credential\X509Certificate;

class ServiceProviderForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'light_saml_idp/dl-horizontal';
    $serviceProvider = $this->getEntity();

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $serviceProvider->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => 'Drupal\light_saml_idp\Entity\ServiceProvider::load',
        'source' => ['serviceProvider', 'id'],
      ],
      '#description' => $this->t('A unique machine-readable name for this Service Provider. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $serviceProvider->label(),
      '#description' => $this->t('The human-readable name of this Service Provider. This text will be displayed to administrators who can configure SAML.'),
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 30,
    ];

    $form['entityId'] = [
      '#type' => 'textfield',
      '#title' => t('Entity ID'),
      '#description' => t('The unique identity of a Service Provider.'),
      '#default_value' => $serviceProvider->getEntityId(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['acsUrl'] = [
      '#type' => 'textfield',
      '#title' => t('Assertion Consumer Service URL'),
      '#description' => t('SAML 2.0 HTTP POST Binding'),
      '#default_value' => $serviceProvider->getAcsUrl(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['signing'] = [
      '#title' => $this->t('Signing'),
      '#description' => $this->t('Required because either the Response or the Assertion element(s) in the Response MUST be signed, if the HTTP POST binding is used'),
      '#type' => 'fieldset',
      '#tree' => FALSE,
    ];
    $form['signing']['signingMessage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Message signing'),
      '#default_value' => $serviceProvider->isSigningMessage(),
    ];
    $form['signing']['signingAssertion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Assertion signing'),
      '#default_value' => $serviceProvider->isSigningAssertion(),
    ];
    $require_signing_state = [
      ['input[name="signingMessage"]' => ['checked' => TRUE]],
      'or',
      ['input[name="signingAssertion"]' => ['checked' => TRUE]],
    ];

    $certificateString = $serviceProvider->getSigningCertificate();

    $certificate = new X509Certificate();
    $suffix = NULL;
    if ($certificateString && $serviceProvider->isValid()) {
      $certificate->setData($certificateString);
      $suffix = _light_saml_idp_cert_info($certificate);
    }

    $form['signing']['signingCertificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('x.509 Certificate'),
      '#description' => t('Enter the signing certificate provided by the Service Provider.'),
      '#default_value' => $certificateString,
      '#states' => ['required' => $require_signing_state],
      '#suffix' => $suffix,
    ];
    $form['signing']['signingMethod'] = [
      '#type' => 'select',
      '#title' => $this->t('Signing method'),
      '#description' => $this->t('The method used to sign.'),
      '#options' => [
        XMLSecurityDSig::SHA1 => 'xmldsig-sha1',
        XMLSecurityDSig::SHA256 => 'xmlenc-sha256',
        XMLSecurityDSig::SHA384 => 'xmldsig-more-sha384',
        XMLSecurityDSig::SHA512 => 'xmlenc-sha512',
      ],
      '#empty_option' => t('- Select -'),
      '#default_value' => $serviceProvider->getSigningMethod(),
      '#states' => ['required' => $require_signing_state],
    ];

    $form['encryption'] = [
      '#title' => $this->t('Assertion encryption'),
      '#type' => 'fieldset',
      '#tree' => FALSE,
    ];
    $form['encryption']['encryptionAssertion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $serviceProvider->isEncryptionAssertion(),
    ];
    $encryption_checked_state = ['input[name="encryptionAssertion"]' => ['checked' => TRUE]];

    $certificate = new X509Certificate();
    $encryptionCertString = $serviceProvider->getEncryptionCertificate();
    $suffix = NULL;
    if ($encryptionCertString && $serviceProvider->isValid()) {
      $certificate->setData($encryptionCertString);
      $suffix = _light_saml_idp_cert_info($certificate);
    }

    $form['encryption']['encryptionCertificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('x.509 certificate'),
      '#description' => t('Enter the certificate provided by the Service Provider for encryption.'),
      '#default_value' => $encryptionCertString,
      '#suffix' => $suffix,
      '#states' => ['required' => [$encryption_checked_state]],
    ];
    $form['encryption']['encryptionMethod'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption method'),
      '#description' => $this->t('The encryption method used to encrypt.'),
      '#options' => [
        XMLSecurityKey::TRIPLEDES_CBC => 'Tripledes CBC',
        XMLSecurityKey::AES128_CBC => 'AES-128 CBC',
        XMLSecurityKey::AES192_CBC => 'AES-192 CBC',
        XMLSecurityKey::AES256_CBC => 'AES-256 CBC',
      ],
      '#empty_option' => t('- Select -'),
      '#default_value' => $serviceProvider->getEncryptionMethod(),
      '#states' => ['required' => [$encryption_checked_state]],
    ];
    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach (['signingCertificate', 'encryptionCertificate'] as $certificate) {
      if (!empty($form_state->getValue($certificate)) && !$this->isValidX509Certificate($form_state->getValue($certificate))) {
        $form_state->setErrorByName($certificate, $this->t('Certificate contains errors.'));
      }
    }
  }

  /**
   * @return \Drupal\light_saml_idp\Entity\ServiceProviderInterface
   */
  public function getEntity(): ServiceProviderInterface {
    return parent::getEntity();
  }

  /**
   * Validate X509Certificate by trying.
   *
   * @param string $data
   *   Certificate data.
   *
   * @return bool
   *   Whether or not the data will parse, i.e. validates.
   */
  protected function isValidX509Certificate(string $data) {
    try {
      $certificate = new X509Certificate();
      $certificate->setData($data);
      return TRUE;
    }
    catch (LightSamlException $exception) {
      return FALSE;
    }
  }

}
