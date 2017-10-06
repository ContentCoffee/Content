<?php

namespace Drupal\content\EventSubscriber;

use Drupal\content\Event\ContentBlockChangedEvent;
use Drupal\content\ContentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentBlockSubscriber implements EventSubscriberInterface
{
    /** @var \Drupal\content\ContentManager */
    private $manager;

    private $updatedHosts = [];

    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents()
    {
        $events[ContentBlockChangedEvent::NAME][] = ['updateHostEntity'];
        return $events;
    }

    /**
     * Trigger an update of the contentblock's host entity
     */
    public function updateHostEntity(ContentBlockChangedEvent $event)
    {
        $host = $this->manager->getHost($event->getContentBlock());
        $cid = sprintf('%s:%s', $host->getEntityTypeId(), $host->id());
        if ($host && !array_key_exists($cid, $this->updatedHosts)) {
            $host->save();
            $this->updatedHosts[$cid] = true;
        }
    }
}
