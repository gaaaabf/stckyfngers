<?php

namespace Drupal\code_deploy_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Class CodeDeployConfigForm.
 */
class CodeDeployConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'code_deploy_connector.codedeployconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'code_deploy_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('code_deploy_connector.codedeployconfig');
    $form['webhook_trigger'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code Deploy URI hook'),
      '#maxlength' => 64,
      '#size' => 64,
      '#attributes' => ['disabled' => 'disabled'],
      '#default_value' => NULL,
      // '#default_value' => $config->get('webhook_trigger'),
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Create Fieldset
    $form['#tree'] = TRUE;
    $form['webhook_fieldset'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE, 
      '#collapsed' => FALSE,
      '#title' => $this->t('Other Web Hooks'),
      '#prefix' => '<div id="webhook-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($form_state->isRebuilding() == FALSE) {
      // Fetch existing webhooks
      $webhooks = $config->get('webhooks');
      unset($webhooks['actions']);
      $trigger_field = $form_state->set('num_webhook', count($webhooks));
    }

    // Fetch existing count of triggers
    $num_webhook = $form_state->get('num_webhook');

    // Ensure that there is at least trigger field.
    if ($num_webhook === NULL) {
      $trigger_field = $form_state->set('num_webhook', 1);
      $num_webhook = 1;
    }

    for ($i = 0; $i < $num_webhook; $i++) {
      $trigger_path = \Drupal::request()->getHost();
      if (!empty($config->get('webhooks')[$i]['url_trigger'])) {
        $trigger_path = \Drupal::request()->getHost() . '/webhook/' . $config->get('webhooks')[$i]['url_trigger'];
      } 

      $form['webhook_fieldset']['web_hook'][$i]['url_trigger'] = [
        '#type' => 'textfield',
        '#description' => 'This URI "/web/hook/" will be prepended to your trigger. Example /web/hook/{your_trigger} <br>Copy path here: <a href="//'.$trigger_path.'">'.$trigger_path.'</a>',
        '#title' => $this->t('Trigger'),
        '#default_value' => $config->get('webhooks')[$i]['url_trigger'],
      ];
      $form['webhook_fieldset']['web_hook'][$i]['command'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Command'),
        '#description' => 'Append 2>&1 if no result output is showing',
        '#default_value' => $config->get('webhooks')[$i]['command'],
      ];
      $form['webhook_fieldset']['web_hook'][$i]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $config->get('webhooks')[$i]['status'],
      ];
      $form['webhook_fieldset']['web_hook'][$i]['auth'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Require Admin Authentication'),
        '#default_value' => $config->get('webhooks')[$i]['auth'],
      ];
      $form['webhook_fieldset']['web_hook'][$i]['method'] = [
        '#type' => 'radios',
        '#options' => [
          'POST' => 'POST',
          'GET' => 'GET',
        ],
        '#title' => $this->t('Method'),
        '#default_value' => (is_null($config->get('webhooks')[$i]['method']) ? 'POST' : $config->get('webhooks')[$i]['method']),
      ];
    }

    // Actions
    $form['webhook_fieldset']['web_hook']['actions'] = [
      '#type' => 'actions',
    ];

    // Add action
    $form['webhook_fieldset']['web_hook']['actions']['add_trigger'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::callBack',
        'wrapper' => 'webhook-fieldset-wrapper',
      ],
    ];

    // If there is more than one, remove action
    if ($num_webhook > 1) {
      $form['webhook_fieldset']['web_hook']['actions']['remove_trigger'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeOne'],
        '#ajax' => [
          'callback' => '::callBack',
          'wrapper' => 'webhook-fieldset-wrapper',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the fields in it.
   */
  public function callBack(array &$form, FormStateInterface $form_state) {
    return $form['webhook_fieldset'];
  }

  /**
   * Submit handler for the "add one more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $trigger_field = $form_state->get('num_webhook');
    $add_button = $trigger_field + 1;
    $form_state->set('num_webhook', $add_button);
    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeOne(array &$form, FormStateInterface $form_state) {
    $trigger_field = $form_state->get('num_webhook');
    if ($trigger_field > 1) {
      $remove_button = $trigger_field - 1;
      $form_state->set('num_webhook', $remove_button);
    }
    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('code_deploy_connector.codedeployconfig')
      ->set('webhook_trigger', $form_state->getValue('webhook_trigger'))
      ->set('webhooks', $form_state->getValue(['webhook_fieldset', 'web_hook']))
      ->save();
  }

}
