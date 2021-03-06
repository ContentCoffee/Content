<?php

/**
 * @file
 * Contains \Drupal\content\Form\ContentContainerForm.
 */

namespace Drupal\content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eck\Entity\EckEntityType;
use Drupal\content\Entity\ContentContainer;

/**
 * Form controller for the container entity type edit form.
 */
class ContentContainerForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        // Get the values.
        $values = $form_state->getValues();
        $types = $this->getContentEntityTypes();
        $firsttype = array_keys($types)[0];

        /** @var ContentContainer $entity */
        $entity = $this->entity;

        // Change page title for the edit operation.
        if ($this->operation == 'edit') {
            $form['#title'] = $this->t('Edit container: @name', array('@name' => $entity->getLabel()));
        }

        $form['wrapper'] = [
            '#prefix' => '<div id="wholewrapper">',
            '#suffix' => '</div>',
        ];

        $form['wrapper']['label'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Container name'),
            '#default_value' => $entity->label(),
            '#size' => 30,
            '#required' => true,
            '#maxlength' => 64,
            '#description' => $this->t('The name for this container.'),
        );

        $form['wrapper']['id'] = array(
            '#type' => 'machine_name',
            '#default_value' => $entity->id(),
            '#required' => true,
            '#disabled' => !$entity->isNew(),
            '#size' => 30,
            '#maxlength' => 64,
            '#machine_name' => [
                'exists' => '\Drupal\content\Entity\ContentContainer::load',
            ],
        );

        $form['wrapper']['host_entity_type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Host entity type'),
            '#default_value' => $entity->getHostEntityType(),
            '#options' => $this->getContentEntityTypes(),
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The host entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => "searching",
                ],
            ],
        );

        $form['wrapper']['host_bundles_fieldset'] = [
            '#title' => t('Host Bundles'),
            '#prefix' => '<div id="host-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];


        $form['wrapper']['host_bundles_fieldset']['host_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $entity->getHostBundlesAll(),
            '#default_value' => $entity->getHostBundles(),
        ];


        $form['wrapper']['child_entity_type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Child entity type'),
            '#default_value' => $entity->getChildEntityType(),
            '#options' => $this->getContentEntityTypes(),
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The child entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => "searching",
                ],
            ],
        );

        $form['wrapper']['child_bundles_fieldset'] = [
            '#title' => t('Child Bundles'),
            '#prefix' => '<div id="child-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];

        $form['wrapper']['child_bundles_fieldset']['child_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $entity->getChildBundlesAll(),
            '#default_value' => $entity->getChildBundles(),
        ];

        $form['wrapper']['child_bundles_default'] = [
            '#title' => t('Default'),
            '#type' => 'select',
            '#options' => $entity->getChildBundlesAll(),
            '#default_value' => $entity->getChildBundlesDefault(),
        ];

        $form['hide_single_option_sizes'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getHideSingleOptionSizes(),
            '#title' => $this->t('Hide single option sizes'),
        ];

        $form['hide_single_option_alignments'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getHideSingleOptionAlignments(),
            '#title' => $this->t('Hide single option alignments'),
        ];


        $form['show_size_column'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getShowSizeColumn(),
            '#title' => $this->t('Show the size column'),
        ];

        $form['show_alignment_column'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getShowAlignmentColumn(),
            '#title' => $this->t('Show the alignment column'),
        ];

        return parent::form($form, $form_state, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** @var ContentContainer $entity */
        $entity = $this->entity;

        // Prevent leading and trailing spaces.
        $entity->set('label', trim($entity->label()));

        $host_bundles = $entity->get('host_bundles');
        $host_bundles = array_filter($host_bundles);
        $entity->set('host_bundles', $host_bundles);

        $child_bundles = $entity->get('child_bundles');
        $child_bundles = array_filter($child_bundles);
        $entity->set('child_bundles', $child_bundles);

        $status = $entity->save();

        $action = $status == SAVED_UPDATED ? 'updated' : 'added';

        // Tell the user we've updated their container.
        drupal_set_message($this->t(
            'Container %label has been %action.',
            [
                '%label' => $entity->label(),
                '%action' => $action
            ]
        ));
        $this->logger('content')->notice(
            'Container %label has been %action.',
            [
                '%label' => $entity->label(),
                '%action' => $action
            ]
        );

        // Redirect back to the list view.
        $form_state->setRedirect('content.collection');
    }

    /**
     * {@inheritdoc}
     */
    protected function actions(array $form, FormStateInterface $form_state)
    {
        $actions = parent::actions($form, $form_state);
        $actions['submit']['#value'] = $this->t('Update container');
        if ($this->entity->isNew()) {
            $actions['submit']['#value'] = $this->t('Add container');
        }
        return $actions;
    }

    /**
     * Ideally filter out only content entity types here. ECK seems to be
     * a config type so however, so bollocks.
     */
    private function getContentEntityTypes()
    {
        $types = [];

        $types['node'] = 'Node';
        $types['taxonomy_term'] = 'Taxonomy Term';

        $eck_types = EckEntityType::loadMultiple();
        foreach ($eck_types as $machine => $type) {
            $types[$machine] = $type->label();
        }

        ksort($types);

        return $types;
    }

    /**
     * @param $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return mixed
     */
    public function updateForm($form, FormStateInterface $form_state)
    {
        $form_state->setRebuild(true);
        return $form['wrapper'];
    }
}
