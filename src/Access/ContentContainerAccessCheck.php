<?php

namespace Drupal\content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content\Entity\ContentContainer;

/**
 * Access check for content container overview.
 */
class ContentContainerAccessCheck implements AccessInterface
{

    /**
     * The entity type manager.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;


    /**
     * ContentContainerAccessCheck constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Checks access to the current container overview for the entity and bundle.
     *
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The parametrized route.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The currently logged in account.
     * @param string $host_type_id
     *   The entity type ID.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access(RouteMatchInterface $route_match, AccountInterface $account, $host_type_id)
    {
        /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $route_match->getParameter($host_type_id);

        /** @var ContentContainer $container */
        $container = $this
            ->entityTypeManager
            ->getStorage('content_container')
            ->load($route_match->getParameter('container'));

        if ($entity && $container && $container->getId()) {
            // Get entity base info.
            $bundle = $entity->bundle();

            // If this bundle is in the list of host bundles, then allow.
            if (empty($container->getHostBundles())
                || array_key_exists($entity->bundle(), $container->getHostBundles())
            ) {
                return AccessResult::allowed();
            }
        }

        // No opinion.
        return AccessResult::neutral();
    }
}
