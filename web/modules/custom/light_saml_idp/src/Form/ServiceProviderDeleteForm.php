<?php

namespace Drupal\light_saml_idp\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ServiceProviderDeleteForm extends EntityDeleteForm {

  /**
   * The IDP to delete.
   *
   * @var array
   */
  protected $idp;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'saml_sp_idp_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Identity Provider %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Nevermind');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function form(array $form, FormStateInterface $form_state) {
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addStatus($this->t('Identity Provider %label has been deleted.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //this needs to be a valid route otherwise the cancel link won't appear
    return new Url('entity.serviceProvider.collection');
  }

}
