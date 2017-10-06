<?php

namespace Drupal\content\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires when a content block has been created/updated
 */
class ContentBlockChangedEvent extends Event
{
    const NAME = 'content.contentblock.changed';
    /** @var \Drupal\Core\Entity\ContentEntityInterface */
    private $contentBlock;
    /** @var \Drupal\content\Entity\ContentContainer[] */
    private $containers;

    public function __construct(EntityInterface $contentBlock, array $containers)
    {
        $this->contentBlock = $contentBlock;
        $this->containers = $containers;
    }

    /** @return \Drupal\Core\Entity\ContentEntityInterface */
    public function getContentBlock()
    {
        return $this->contentBlock;
    }

    /**
     * @return \Drupal\content\Entity\ContentContainer[]
     */
    public function getContainers()
    {
        return $this->containers;
    }
}
