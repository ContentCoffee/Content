<?php

namespace Drupal\content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeBundleInfo as CoreBundleInfo;
use Drupal\content\ContentContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the content_container entity.
 *
 * @ConfigEntityType(
 *   id = "content_container",
 *   label = @Translation("Content Container"),
 *   handlers = {
 *     "list_builder" = "Drupal\content\ContentContainerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\content\Form\ContentContainerForm",
 *       "edit" = "Drupal\content\Form\ContentContainerForm",
 *       "delete" = "Drupal\content\Form\ContentContainerDeleteForm",
 *     }
 *   },
 *   config_prefix = "content_container",
 *   admin_permission = "administer content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/containers",
 *     "add-form" = "/admin/config/content/containers/add",
 *     "edit-form" = "/admin/config/content/containers/{content_container}",
 *     "delete-form" = "/admin/config/content/containers/{content_container}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "host_entity_type",
 *     "host_bundles",
 *     "child_entity_type",
 *     "child_bundles",
 *     "child_bundles_default",
 *     "hide_single_option_sizes",
 *     "hide_single_option_alignments",
 *     "show_size_column",
 *     "show_alignment_column"
 *   }
 * )
 */
class ContentContainer extends ConfigEntityBase implements ContentContainerInterface
{

    /** @var CoreBundleInfo */
    private $entityTypeBundleInfo;

    /**
     * The Content Container ID.
     *
     * @var string
     */
    public $id;

    /**
     * The Content Container label.
     *
     * @var string
     */
    public $label;

    /**
     * The Content Container host entity type.
     *
     * @var string
     */
    public $host_entity_type = '';

    /**
     * The Content Container host entity bundles.
     *
     * @var array
     */
    public $host_bundles = [];

    /**
     * The Content Container child entity types.
     *
     * @var string
     */
    public $child_entity_type = '';

    /**
     * The Content Container child entity bundles.
     *
     * @var array
     */
    public $child_bundles = [];

    /**
     * The Content Container child entity bundles default.
     *
     * @var string
     */
    public $child_bundles_default;

    /**
     * If the options for sizes is is just one option do we then
     * hide the field in the form?
     * @var bool
     */
    public $hide_single_option_sizes = false;

    /**
     * If the options for the alignments is just one option
     * then do we hide the field in the form.
     * @var bool
     */
    public $hide_single_option_alignments = false;

    /*
     * If this is turned on then we show the column in the master container table.
     */
    public $show_size_column = true;

    /*
     * If this is turn then we will show the column in the master container table.
     */
    public $show_alignment_column = true;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getHostEntityType()
    {
        return $this->host_entity_type;
    }

    /**
     * {@inheritdoc}
     */
    public function getHostBundles()
    {
        return $this->host_bundles;
    }

    /**
     * @return array
     */
    public function getHostBundlesAll()
    {
        return $this->allBundles($this->getHostEntityType());
    }

    /**
     * {@inheritdoc}
     */
    public function getChildEntityType()
    {
        return $this->child_entity_type;
    }


    /**
     * {@inheritdoc}
     */
    public function getChildBundles()
    {
        return $this->child_bundles;
    }

    /**
     * @return array
     */
    public function getChildBundlesAll()
    {
        return $this->allBundles($this->getChildEntityType());
    }

    /**
     * @return array
     */
    public function getChildBundlesDefault()
    {
        return $this->child_bundles_default;
    }

    /**
     * @param $type
     *
     * @return array
     */
    private function allBundles($type)
    {
        $bundles = array_keys($this->entityTypeBundleInfo()->getBundleInfo($type));
        sort($bundles);
        $return = [];
        foreach ($bundles as $bundle) {
            $return[$bundle] = $bundle;
        }
        return $return;
    }

    /**
     * @return bool
     */
    public function getHideSingleOptionSizes()
    {
        return $this->hide_single_option_sizes;
    }

    /**
     * @return bool
     */
    public function getHideSingleOptionAlignments()
    {
        return $this->hide_single_option_alignments;
    }

    /**
     * @return bool
     */
    public function getShowSizeColumn()
    {
        return $this->show_size_column;
    }

    /**
     * @return bool
     */
    public function getShowAlignmentColumn()
    {
        return $this->show_alignment_column;
    }

    /**
     * @return \Drupal\Core\Entity\EntityTypeBundleInfo|object
     */
    private function entityTypeBundleInfo()
    {
        if (!$this->entityTypeBundleInfo) {
            $this->entityTypeBundleInfo = $this->container()->get('entity_type.bundle.info');
        }
        return $this->entityTypeBundleInfo;
    }

    /**
     * Returns the service container.
     *
     * This method is marked private to prevent sub-classes from retrieving
     * services from the container through it. Instead,
     * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
     * for injecting services.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The service container.
     */
    private function container()
    {
        return \Drupal::getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'host_entity_type' => $this->getHostEntityType(),
            'child_entity_type' => $this->getChildEntityType(),
            'host_bundles' => $this->getHostBundles(),
            'child_bundles' => $this->getChildBundles(),
            'child_bundles_default' => $this->getChildBundlesDefault(),
            'hide_single_option_sizes' => $this->getHideSingleOptionSizes(),
            'hide_single_option_alignments' => $this->getHideSingleOptionAlignments(),
            'show_size_column' => $this->getShowSizeColumn(),
            'show_alignment_column' => $this->getShowAlignmentColumn()
        ];

        if (empty($config['host_bundles'])) {
            // Load them all.
            $config['host_bundles'] = $this->getHostBundlesAll();
        }
        if (empty($config['child_bundles'])) {
            // Load them all.
            $config['child_bundles'] = $this->getChildBundlesAll();
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function isHost(EntityInterface $host)
    {
        return $host->getEntityTypeId() == $this->getHostEntityType()
            && in_array($host->bundle(), $this->getHostBundles());
    }

    /**
     * Check whether an entity is a ContentBlock for this container
     */
    public function hasContentBlock(EntityInterface $contentBlock)
    {
        // We consider this entity a contentblock if it has the configured
        // entityTypeId (eg: content_block) and bundle (eg: section)
        $childBundles = $this->getChildBundles();
        return $this->getChildEntityType() == $contentBlock->getEntityTypeId()
            && (
                empty($childBundles)
                || array_key_exists(
                    $contentBlock->bundle(),
                    $childBundles
                )
            );
    }

    /**
    * {@inheritdoc}
    */
    public function postSave(EntityStorageInterface $storage, $update = false)
    {
        parent::postSave($storage, $update);

        // Add our base fields to the schema.
        \Drupal::service('entity.definition_update_manager')->applyUpdates();
        drupal_flush_all_caches();
    }

    /**
    * {@inheritdoc}
    */
    public static function postDelete(EntityStorageInterface $storage, array $entities)
    {
        parent::postDelete($storage, $entities);

        // Add our base fields to the schema.
        \Drupal::service('entity.definition_update_manager')->applyUpdates();
        drupal_flush_all_caches();
    }

}
