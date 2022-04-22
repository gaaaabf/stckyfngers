<?php

namespace Drupal\custom_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ArtistsRegistration extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'artists_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if ($form_state->has('page') && $form_state->get('page') == 2) {
      return self::buildFormTwo($form, $form_state);
    }

    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First Name:'),
      '#required' => TRUE,
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last Name:'),
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email:'),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::submitPageOne'],
      // '#validate' => ['::validatePageOne'],
    ];
    return $form;
  }

  public function submitPageOne(array &$form, FormStateInterface $form_state) {
    $form_state->set('page_1_values', [
      'first_name' => $form_state->getValue('first_name'),
      'last_name' => $form_state->getValue('last_name'),
      'email' => $form_state->getValue('email'),
    ]);
    $form_state->set('page', 2);
    $form_state->setRebuild(TRUE);
  }

  public function buildFormTwo(array &$form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#title' => t('Page @page',['@page' => $form_state->get('page')]),
    ];
    $form['color'] = [
      '#type' => 'textfield',
      '#title' => t('Favorite color'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('color', ''),
    ];
    $form['back'] = [
      '#type' => 'submit',
      '#value' => t('Back'),
      '#submit' => ['::pageTwoBack'],
      '#limit_validation_errors' => [],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function pageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state->setValues($form_state->get('page_1_values'));
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // \Kint::dump($form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('page_2_values', [
      'color' => $form_state->getValue('color'),
    ]);

    $page_1_values = $form_state->get('page_1_values');
    $page_2_values = $form_state->get('page_2_values');

    \Kint::dump($page_1_values);
    \Kint::dump($page_2_values);
    

    die();
  }
}