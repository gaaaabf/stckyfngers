<?php

namespace Drupal\light_saml_idp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use LightSaml\Credential\X509Certificate;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class LightSAMLIdpConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'light_saml_idp_config_idp';
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   SAML SP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('light_saml_idp.settings')->get($name);
    $current = $this->configFactory->get('light_saml_idp.settings')->get($name);
    return $original != $current;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('light_saml_idp.settings');
    $values = $form_state->getValues();

    $this->configRecurse($config, $values['contact'], 'contact');
    $this->configRecurse($config, $values['organization'], 'organization');
    $this->configRecurse($config, $values['security'], 'security');
    $config->set('entity_id', $values['entity_id']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach (['key_location', 'cert_location'] AS $key) {
      if (!file_exists($values['security']['signing'][$key])) {
        $form_state->setError($form['security']['signing'][$key], $this->t('The %input file does not exist.', array('%input' => $values['security']['signing'][$key])));
      }
    }
  }

  /**
   * recursively go through the set values to set the configuration
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values AS $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value)) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['light_saml_idp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'light_saml_idp/dl-horizontal';
    $config = $this->configFactory->get('light_saml_idp.settings');

    $form['entity_id'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Entity ID'),
      '#description'    => $this->t('It is recommended that the URI is a URL that contains the domain name of the entity.'),
      '#default_value'  => $config->get('entity_id'),
      '#disabled'       => $this->isOverridden('entity_id'),
      '#required'       => TRUE,
    );

    $form['contact'] = array(
      '#type'         => 'fieldset',
      '#title'        => $this->t('Contact Information'),
      '#description'  => $this->t('Information to be included in the federation metadata.'),
      '#tree'         => TRUE,
    );
    $form['contact']['support'] = array(
      '#type'         => 'fieldset',
      '#title'        => $this->t('Support'),
    );
    $form['contact']['support']['name'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Name'),
      '#default_value'  => $config->get('contact.support.name'),
      '#disabled'       => $this->isOverridden('contact.support.name'),
    );
    $form['contact']['support']['email'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Email'),
      '#default_value'  => $config->get('contact.support.email'),
      '#disabled'       => $this->isOverridden('contact.support.email'),
    );

    $form['organization'] = array(
      '#type'           => 'fieldset',
      '#title'          => $this->t('Organization'),
      '#tree'           => TRUE,
    );
    $form['organization']['name'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Name'),
      '#default_value'  => $config->get('organization.name'),
      '#disabled'       => $this->isOverridden('organization.name'),
    );
    $form['organization']['display_name'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Display Name'),
      '#default_value'  => $config->get('organization.display_name'),
      '#disabled'       => $this->isOverridden('organization.display_name'),
    );
    $form['organization']['url'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('URL'),
      '#default_value'  => $config->get('organization.url'),
      '#disabled'       => $this->isOverridden('organization.url'),
    );

    $form['security'] = array(
      '#type'           => 'fieldset',
      '#title'          => $this->t('Security'),
      '#tree'           => TRUE,
    );
    $form['security']['signing'] = array(
      '#type'           => 'fieldset',
      '#title'          => $this->t('Signing'),
      '#tree'           => TRUE,
    );
    $form['security']['signing']['cert_location'] = array(
      '#type'   => 'textfield',
      '#title'  => $this->t('Certificate location'),
      '#description'  => $this->t('The location of the x.509 certificate file on the server. This must be a location that PHP can read.'),
      '#default_value' => $config->get('cert_location'),
      '#disabled'      => $this->isOverridden('security.signing.cert_location'),
      '#required'      => TRUE,
      '#suffix' => $this->certInfo($config->get('security.signing.cert_location')),
    );

    $form['security']['signing']['key_location'] = array(
      '#type'   => 'textfield',
      '#title'  => $this->t('Key location'),
      '#description'  => $this->t('The location of the x.509 key file on the server. This must be a location that PHP can read.'),
      '#default_value' => $config->get('key_location'),
      '#disabled'      => $this->isOverridden('key_location'),
      '#required'      => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Output information given a file location of a certificate.
   */
  function certInfo($cert_location) {
    if (!empty($cert_location) && file_exists($cert_location) && function_exists('openssl_x509_parse')) {
      $certificate = X509Certificate::fromFile($cert_location);
      return _light_saml_idp_cert_info($certificate);
    }
    return null;
  }
}
