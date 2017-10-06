<?php

namespace Drupal\content\Controller;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\content\Entity\ContentContainer;
use Drupal\content\ContentDescriptiveTitles;
use Drupal\content\ContentManager;
use Drupal\content\Form\ContentMasterForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base class for content controllers.
 */
class ContentController extends ControllerBase
{
    /** @var ContentManager */
    protected $contentManager;

    /** @var FormBuilderInterface */
    protected $formBuilder;

    /** @var ContentDescriptiveTitles */
    protected $descriptiveTitles;
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface */
    private $entityTypeBundleInfo;

    /**
     * ContentController constructor.
     * @param ContentManager $contentManager
     * @param FormBuilderInterface $formBuilder
     * @param ContentDescriptiveTitles $descriptiveTitles
     */
    public function __construct(
        ContentManager $contentManager,
        FormBuilderInterface $formBuilder,
        ContentDescriptiveTitles $descriptiveTitles,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo
    ) {
        $this->contentManager = $contentManager;
        $this->formBuilder = $formBuilder;
        $this->descriptiveTitles = $descriptiveTitles;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        /** @var ContentManager $contentManager */
        $contentManager = $container->get('content.manager');
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $container->get('form_builder');
        /** @var ContentDescriptiveTitles */
        $descriptiveTitles = $container->get('content.descriptive_titles');

        return new static(
            $contentManager,
            $formBuilder,
            $descriptiveTitles,
            $container->get('entity_type.bundle.info')
        );
    }

    /**
     * @param $container
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     * @param null $host_type_id
     *
     * @return array
     */
    public function overview($container, RouteMatchInterface $route_match, $host_type_id = null)
    {
        $build = [];
        // Get the container.
        /** @var ContentContainer $current_container */
        $current_container = $this->entityTypeManager()->getStorage('content_container')->load($container);
        $host_entity = $route_match->getParameter($host_type_id);

        if ($current_container->getId()) {
            // Start a form.
            $form = new ContentMasterForm(
                $this->contentManager,
                $this->entityTypeBundleInfo,
                $host_entity,
                $current_container
            );
            $build['#title'] = $this->t(
                '%slug for %label',
                [
                    '%slug' => $current_container->getLabel(),
                    '%label' => $host_entity->label(),
                ]
            );
            $build['form'] = $this->formBuilder->getForm($form);
        } else {
            throw new NotFoundHttpException(
                $this->t('Container @container does not exist.', ['@container' => $container])
            );
        }
        return $build;
    }

    /**
     * @param $container
     * @param $bundle
     * @param \Drupal\Core\Routing\RouteMatchInterface $route
     * @param $host_type_id
     *
     * @return array
     */
    public function add($container, $bundle, RouteMatchInterface $route, $host_type_id)
    {
        // Get the container.
        /** @var ContentContainer $current_container */
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('content_container')
            ->load($container);

        $host = $route->getParameter($host_type_id);

        $weight = 0;
        $blocks = $this->contentManager->getContent($host, $current_container->id());
        foreach ($blocks as $block) {
            /* @var \Drupal\Core\Entity\ContentEntityInterface $block */
            if (!$block->hasField('content_weight')) {
                continue;
            }

            $blockWeight = $block->get('content_weight')->getString();

            $weight = $blockWeight > $weight ? $blockWeight : $weight;
        }


        // Create an empty entity of the chosen entity type and the bundle.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->create(
                [
                    'type' => $bundle,
                ]
            );

        // Get the id of the parent and add it in.
        $child->set('content_parent', $host->id());
        $child->set('content_parent_type', $host_type_id);
        $child->set('content_size', 'full');
        $child->set('content_alignment', 'left');
        $child->set('content_weight', $weight + 1);
        $child->set('content_container', $current_container->getId());

        // In the correct language.
        $child->set('langcode', $host->get('langcode')->value);

        // Get the form.
        $form = $this->entityFormBuilder()->getForm($child);

        // Hide some stuff.
        $form['content_container']['#access'] = false;
        $form['content_parent_type']['#access'] = false;
        $form['content_parent']['#access'] = false;
        $form['content_weight']['#access'] = false;

        // Change the 'Add another item' button label
        $this->descriptiveTitles->updateAddMoreButtonTitle($form, $child);
        $this->descriptiveTitles->updateAddAnotherSubContentButtonTitle($form, $child);

        return $form;
    }

    /**
     * @param $container
     * @param $child_id
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     * @param null $host_type_id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete($container, $child_id, RouteMatchInterface $route_match, $host_type_id = null)
    {
        // Get the container.
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('content_container')
            ->load($container);

        $host = $route_match->getParameter($host_type_id);

        // Load up the child.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->load($child_id);

        $child->delete();

        drupal_set_message(
            $this->t(
                '%container_label %name has been deleted.',
                [
                    '%container_label' => $current_container->getLabel(),
                    '%name' => $child->label(),
                ]
            )
        );

        return $this->redirect(
            'entity.' . $current_container->getHostEntityType() . '.content_overview',
            [
                $current_container->getHostEntityType() => $host->id(),
                'container' => $current_container->id(),
            ]
        );
    }

    /**
     * @param $container
     * @param $child_id
     *
     * @return array
     */
    public function edit($container, $child_id)
    {
        // Get the container.
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('content_container')
            ->load($container);

        // Load up the child.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->load($child_id);

        // Get the form.
        $form = $this->entityFormBuilder()->getForm($child);

        // Hide some stuff.
        $form['content_container']['#access'] = false;
        $form['content_parent_type']['#access'] = false;
        $form['content_parent']['#access'] = false;
        $form['content_weight']['#access'] = false;

        // Change the 'Add another item' button label
        $this->descriptiveTitles->updateAddMoreButtonTitle($form, $child);
        $this->descriptiveTitles->updateAddAnotherSubContentButtonTitle($form, $child);

        // Get the form and return it.
        return $form;
    }
}
