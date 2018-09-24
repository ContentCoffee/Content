<?php

namespace Drupal\content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content\Entity\ContentContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for content.
 *
 * @see \Drupal\content\Plugin\Menu\ContextualLink\ContentContextualLinks
 */
class ContentContextualLinks extends DeriverBase implements ContainerDeriverInterface
{

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
    protected $entityTypeManager;


    /**
     * ContentContextualLinks constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        /** @var EntityTypeManagerInterface $entityTypeManager */
        $entityTypeManager = $container->get('entity_type.manager');
        return new static(
            $entityTypeManager
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivativeDefinitions($base_plugin_definition)
    {
        // Load all config.
        $storage = $this->entityTypeManager->getStorage('content_container');

        /** @var ContentContainer $container */
        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            $key = $config['host_entity_type'] . '.' . $config['id'];

            $this->derivatives[$key]['title'] = $config['label'];
            $this->derivatives[$key]['route_name'] = 'entity.' . $config['host_entity_type'] . '.content_overview';
            $this->derivatives[$key]['group'] = $config['host_entity_type'];
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
