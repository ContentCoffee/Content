<?php

namespace Drupal\content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content\Entity\ContentContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides dynamic local tasks for content.
 */
class ContentLocalTasks extends DeriverBase implements ContainerDeriverInterface
{
    use StringTranslationTrait;

    /**
     * The base plugin ID
     *
     * @var string
     */
    protected $basePluginId;

    /**
     * The entity manager.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;


    /**
     * ContentLocalTasks constructor.
     *
     * @param $base_plugin_id
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
     */
    public function __construct(
        $base_plugin_id,
        EntityTypeManagerInterface $entityTypeManager,
        TranslationInterface $stringTranslation
    ) {
        $this->basePluginId = $base_plugin_id;
        $this->entityTypeManager = $entityTypeManager;
        $this->stringTranslation = $stringTranslation;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        /** @var EntityTypeManagerInterface $entityTypeManager */
        $entityTypeManager = $container->get('entity_type.manager');
        /** @var TranslationInterface $stringTranslation */
        $stringTranslation = $container->get('string_translation');

        return new static(
            $base_plugin_id,
            $entityTypeManager,
            $stringTranslation
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

            // Get the route name for the content overview.
            $content_route_name = 'entity.' . $config['host_entity_type'] . '.content_overview';

            $base_route_name = "entity." . $config['host_entity_type'] . ".canonical";

            $this->derivatives[$content_route_name . '.' . $config['id']] = array(
                'entity_type' => $config['host_entity_type'],
                'title' => $config['label'],
                'route_name' => $content_route_name,
                'route_parameters' => [
                    'container' => $config['id'],
                ],
                'base_route' => $base_route_name,
                'cache' => false,
            ) + $base_plugin_definition;
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
