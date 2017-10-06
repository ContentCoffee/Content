<?php

/**
 * @file
 * Contains \Drupal\content\Form\ContentContainerDeleteForm.
 */

namespace Drupal\content\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for content container entity.
 */
class ContentContainerDeleteForm extends EntityConfirmFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete the container %name?', array('%name' => $this->entity->label()));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('content.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->entity->delete();
        $this->logger('content_container_entity')->notice('Container %name has been deleted.', array('%name' => $this->entity->label()));
        drupal_set_message($this->t('Container %name has been deleted.', array('%name' => $this->entity->label())));
        $form_state->setRedirectUrl($this->getCancelUrl());
    }

}
