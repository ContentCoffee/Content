<?php

namespace Drupal\content\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Provides a content link for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_overview_link")
 */
class ContentOverview extends EntityLink
{

    /**
     * {@inheritdoc}
     */
    protected function getEntityLinkTemplate()
    {
        return 'drupal:content-overview';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultLabel()
    {
        return $this->t('Content');
    }

}
