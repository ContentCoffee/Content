<?php

namespace Drupal\content\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\content\Entity\ContentContainer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity content routes.
 */
class ContentRouteSubscriber extends RouteSubscriberBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    /**
     * ContentRouteSubscriber constructor.
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
    protected function alterRoutes(RouteCollection $collection)
    {
        $storage = $this->entityTypeManager->getStorage('content_container');

        /** @var ContentContainer $container */
        foreach ($storage->loadMultiple() as $container) {
            // Get the config.
            $config = $container->getConfig();

            // Load the host type.
            $host_type = $this->entityTypeManager->getDefinition($config['host_entity_type']);

            // Try to get the route from the current collection.
            $link_template = $host_type->getLinkTemplate('canonical');
            if (strpos($link_template, '/') !== false) {
                $base_path = '/' . $link_template;
            } else {
                if (!$entity_route = $collection->get('entity.' . $config['host_entity_type'] . '.canonical')) {
                    continue;
                }
                $base_path = $entity_route->getPath();
            }

            // Inherit admin route status from edit route, if exists.
            $is_admin = false;
            $route_name = 'entity.' . $config['host_entity_type'] . '.edit_form';
            $edit_route = $collection->get($route_name);
            if ($edit_route) {
                $is_admin = (bool)$edit_route->getOption('_admin_route');
            }

            // Set a base path.
            $path = $base_path . '/content/{container}';

            // Overview.
            $route = new Route(
                $path,
                [

                    '_controller' => '\Drupal\content\Controller\ContentController::overview',
                    'host_type_id' => $config['host_entity_type'],
                    'container' => $config['id'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                    '_content_container_view_access' => $config['host_entity_type'],
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $is_admin,
                ]
            );
            $route_name = 'entity.' . $config['host_entity_type'] . '.content_overview';

            $collection->add($route_name, $route);

            // Add.
            $route = new Route(
                $path . '/add/{bundle}',
                [
                    '_controller' => '\Drupal\content\Controller\ContentController::add',
                    '_title_callback' => 'content.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.content_add', $route);

            // Edit.
            $route = new Route(
                $path . '/{child_id}/edit',
                [
                    '_controller' => '\Drupal\content\Controller\ContentController::edit',
                    '_title_callback' => 'content.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.content_edit', $route);

            // Delete.
            $route = new Route(
                $path . '/{child_id}/delete',
                [
                    '_controller' => '\Drupal\content\Controller\ContentController::delete',
                    '_title_callback' => 'content.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.content_delete', $route);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = parent::getSubscribedEvents();
        // Should run after AdminRouteSubscriber so the routes can inherit admin
        // status of the edit routes on entities. Therefore priority -210.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
        return $events;
    }
}
