<?php

namespace Drupal\content\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\content\ContentManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ContentMasterForm.
 *
 * @package Drupal\content\Form
 */
class ContentMasterForm extends FormBase
{

    /** @var ContentManager */
    protected $contentManager;

    /**
     * The main entity that we are adding and managing paragraphs for.
     */
    protected $host;

    /**
     * @var Drupal\content\Entity\ContentContainer $container
     */
    protected $container;

    /**
     * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
     */
    protected $entityTypeBundleInfo;

    /**
     * ContentMasterForm constructor.
     *
     * @param \Drupal\content\ContentManager $contentManager
     * @param \Drupal\Core\Entity\EntityInterface $host
     * @param $container
     */
    public function __construct(
        ContentManager $contentManager,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo,
        EntityInterface $host,
        $container
    ) {
        $this->contentManager = $contentManager;
        $this->host = $host;
        $this->container = $container;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'content_master_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->container->getConfig();

        // Get the children.
        $children = $this->contentManager->getContent(
            $this->host,
            $this->container->getId()
        );

        // The query (including the destination) Will be the same for all actions.
        // We must add our own language param, however, since adding it in via
        // link parameters is a no go.
        $query = [
            'destination' => Url::fromRoute(
                "entity." . $this->container->getHostEntityType() . ".content_overview",
                [
                    $this->container->getHostEntityType() => $this->host->id(),
                    'container' => $this->container->getId(),
                ]
            )->toString(),
        ];
        if (!empty($_GET['language_content_entity'])) {
            $query['language_content_entity'] = $_GET['language_content_entity'];
        }

        // Some internal values.
        $form['container'] = [
            '#type' => 'value',
            '#value' => $this->container,
        ];

        // Some internal values.
        $form['host'] = [
            '#type' => 'value',
            '#value' => $this->host,
        ];

        $header = [];
        $header[] = '';
        $header[] = t('Type');
        $header[] = t('Content');
        if ($this->container->getShowSizeColumn()) {
            $header[] = t('Size');
        }
        if ($this->container->getShowAlignmentColumn()) {
            $header[] = t('Alignment');
        }
        $header[] = t('Weight');
        $header[] = t('Operations');

        // Put it in a Table.
        $form['rows'] = [
            '#tree' => true,
            '#type' => 'table',
            '#header' => $header,
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => 'content_weight',
                ],
            ],
        ];

        /** @var Drupal\eck\Entity\EckEntity $child */
        foreach ($children as $child) {
            // Edit and delete operations.
            $operations = [
                'data' => [
                    '#type' => 'operations',
                    '#links' => [
                        'edit' => [
                            'url' => Url::fromRoute(
                                "entity." . $this->container->getHostEntityType() . ".content_edit",
                                [
                                    'container' => $this->container->getId(),
                                    'type' => $child->getEntityTypeId(),
                                    'child_id' => $child->id(),
                                    $this->container->getHostEntityType() => $this->host->id(),
                                ],
                                [
                                    'query' => $query,
                                ]
                            ),
                            'title' => $this->t('Edit'),
                        ],
                        'delete' => [
                            'url' => Url::fromRoute(
                                "entity." . $this->container->getHostEntityType() . ".content_delete",
                                [
                                    'container' => $this->container->getId(),
                                    'type' => $child->getEntityTypeId(),
                                    'child_id' => $child->id(),
                                    $this->container->getHostEntityType() => $this->host->id(),
                                ],
                                [
                                    'query' => $query,
                                ]
                            ),
                            'title' => $this->t('Delete'),
                        ],
                    ],
                ],
            ];

            // Start the row.
            $row = [
                '#attributes' => [
                    'class' => ['draggable'],
                ],
            ];

            // Put type and id in a hidden.
            $row['hiddens'] = [];
            $row['hiddens']['id'] = [
                '#type' => 'hidden',
                '#value' => $child->id(),
            ];

            $row['hiddens']['type'] = [
                '#type' => 'hidden',
                '#value' => $child->getEntityTypeId(),
            ];

            $row['hiddens']['bundle'] = [
                '#type' => 'hidden',
                '#value' => $child->get('type')->entity->id(),
            ];

            // Bundle label.
            $row['bundle'] = [
                '#type' => 'container',
                '#markup' => $child->get('type')->entity->label(),
            ];

            // Teaser.
            $row['content'] = [
                '#type' => 'container',
                '#markup' => $child->label(),
            ];

            if ($this->container->getShowSizeColumn()) {
                // Size?
                $row['size'] = [
                    '#type' => 'container',
                    '#markup' => $child->get('content_size')->getString(),
                ];
            }

            if ($this->container->getShowAlignmentColumn()) {
                // Alignment?
                $row['alignment'] = [
                    '#type' => 'container',
                    '#markup' => $child->get('content_alignment')->getString(),
                ];
            }

            // Weight.
            $row['content_weight'] = [
                '#type' => 'weight',
                '#default_value' => $child->get('content_weight')->getString(),
                '#attributes' => [
                    'class' => ['content_weight', 'content_weight-' . $child->id()],
                ],
                '#delta' => 100,
            ];

            // Add the operations.
            $row['operations'] = $operations;

            // Add the row to the rows.
            $form['rows'][] = $row;
        }

        // Make some add links.
        $links = [];

        // Put the default one on first if it is set/existing.
        if (isset($config['child_bundles'][$config['child_bundles_default']])) {
            $key = $config['child_bundles_default'];
            $element = $config['child_bundles'][$config['child_bundles_default']];
            $config['child_bundles'] = [$key => $element] + $config['child_bundles'];
        }

        foreach ($config['child_bundles'] as $bundle) {
            $links[$bundle] = [
                'title' => $this->t(
                    'Add %label',
                    [
                        '%label' => $this->getLabel($config['child_entity_type'], $bundle)
                    ]
                ),
                'url' => Url::fromRoute(
                    "entity." . $this->container->getHostEntityType() . ".content_add",
                    [
                        'bundle' => $bundle,
                        $this->container->getHostEntityType() => $this->host->id(),
                        'container' => $this->container->getId(),
                    ],
                    [
                        'query' => $query,
                    ]
                ),
            ];
        }

        // Submit or add.
        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => t('Save the order'),
            '#weight' => 0,
        ];

        $form['actions']['add_new'] = [
            '#type' => 'dropbutton',
            '#links' => $links,
            '#weight' => 1,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Get the values from the form.
        $values = $form_state->getValues();
        $rows = $values['rows'] ?: [];

        // Go through each row and update the weight.
        foreach ($rows as $row) {
            /** @var Drupal\eck\Entity\EckEntity $p */
            $p = Drupal::entityTypeManager()->getStorage($row['hiddens']['type'])->load($row['hiddens']['id']);
            $p->set('content_weight', $row['content_weight']);
            $p->save();
        }

        if (\Drupal::request()->isXmlHttpRequest()) {
            $form_state->setResponse(new JsonResponse('ok'));
        }
    }

    private function getLabel($entityType, $bundle)
    {
        $labels = &drupal_static(__FUNCTION__);
        if (!isset($labels[$entityType])) {
            $labels[$entityType] = $this->entityTypeBundleInfo->getBundleInfo($entityType);
        }

        if (!empty($labels[$entityType][$bundle]['label'])) {
            return $labels[$entityType][$bundle]['label'];
        }
        return ucwords(str_replace("_", " ", $bundle));
    }
}
