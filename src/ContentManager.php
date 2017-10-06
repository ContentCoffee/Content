<?php

namespace Drupal\content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content\Entity\ContentContainer;
use Drupal\content\Event\ContentBlockChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class ContentManager
{
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var CacheBackendInterface */
    protected $cacheBackend;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EventDispatcherInterface $event_dispatcher,
        CacheBackendInterface $cacheBackend
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->eventDispatcher = $event_dispatcher;
        $this->cacheBackend = $cacheBackend;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(ContentEntityInterface $host, $container)
    {
        $data = &drupal_static(__FUNCTION__);

        if (!$container instanceof ContentContainer) {
            $container = $this->getContainer($container);
        }

        $storage = $this->entityTypeManager->getStorage($container->getChildEntityType());

        $key = 'content:' . $container->id() . ':' . $host->getEntityTypeId() . ':' . $host->id() . ':' . $host->get('langcode')->value;

        // Return statically cached data
        if (isset($data[$key])) {
            return $storage->loadMultiple($data[$key]);
        }

        // Return database-cached data
        if ($cache = $this->cacheBackend->get($key)) {
            $data[$key] = $cache->data;
            return $storage->loadMultiple($data[$key]);
        }

        // Query database
        $data[$key] = $storage
            ->getQuery()
            ->condition('content_parent', $host->id())
            ->condition('content_parent_type', $host->getEntityTypeId())
            ->condition('langcode', $host->language()->getId())
            ->condition('content_container', $container->id())
            ->sort('content_weight', 'ASC')
            ->execute();

        // Put in cache. Mind the invalidating array that should invalidate
        // this cache when the node gets cleared.
        $this->cacheBackend->set(
            $key,
            $data[$key],
            CacheBackendInterface::CACHE_PERMANENT,
            $host->getCacheTags()
        );

        return $storage->loadMultiple($data[$key]);
    }

    public function getHost(ContentEntityInterface $contentBlock)
    {
        if (!$this->isContentBlock($contentBlock)) {
            return null;
        }

        return $this
            ->entityTypeManager
            ->getStorage($contentBlock->get('content_parent_type')->value)
            ->load($contentBlock->get('content_parent')->value);
    }

    public function isContentBlock(ContentEntityInterface $contentBlock)
    {
        return $contentBlock->hasField('content_parent') && !$contentBlock->get('content_parent')->isEmpty();
    }

    /** @return ContentContainer[] */
    public function getHostContainers(ContentEntityInterface $host)
    {
        return array_filter(
            $this->getContainers(),
            function (ContentContainer $container) use ($host) {
                return $container->isHost($host);
            }
        );
    }

    /** @return ContentContainer[] */
    public function getContainers(ContentEntityInterface $contentBlock = null)
    {
        $containers = $this
            ->entityTypeManager
            ->getStorage('content_container')
            ->loadMultiple();

        if (!$contentBlock) {
            return $containers;
        }

        return array_filter(
            $containers,
            function (ContentContainer $container) use ($contentBlock) {
                return $container->hasContentBlock($contentBlock);
            }
        );
    }

    public function emitChangedEvent(EntityInterface $contentBlock, array $containers)
    {
        $this->eventDispatcher->dispatch(
            ContentBlockChangedEvent::NAME,
            new ContentBlockChangedEvent($contentBlock, $containers)
        );
    }

    /** @return \Drupal\content\Entity\ContentContainer */
    private function getContainer($containerName)
    {
        $container = $this
            ->entityTypeManager
            ->getStorage('content_container')
            ->load($containerName);

        if (!$container) {
            throw new \Exception(sprintf(
                'Could not find content container `%s`',
                $containerName
            ));
        }

        return $container;
    }
}
