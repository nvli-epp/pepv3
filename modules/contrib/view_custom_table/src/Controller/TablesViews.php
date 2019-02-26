<?php

namespace Drupal\view_custom_table\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Defines OwnTableList class.
 */
class TablesViews extends ControllerBase {

  /**
   * Entity Manager for calss.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Display views custom table for the logedin user.
   *
   * @return array
   *   Return markup array of views custom table created by logedin user.
   */
  public function content($table_name = NULL) {
    $connection = Database::getConnection();
    if ($connection->schema()->tableExists('custom_table_view_data')) {
      $properties = ['base_table' => $table_name];
      $views = $this->entityManager->getStorage('view')->loadByProperties($properties);
      if (!empty($views)) {
        foreach ($views as $machine_name => $view) {
          $perameter = [
            'view' => $machine_name,
          ];
          $options = [
            'query' => [
              'destination' => 'admin/structure/views/custom_table/views/' . $table_name,
            ],
          ];
          $edit_url = Url::fromRoute('entity.view.edit_form', $perameter, $options);
          $duplicate_url = Url::fromRoute('entity.view.duplicate_form', $perameter, $options);
          $enable_url = Url::fromRoute('entity.view.enable', $perameter, $options);
          $disable_url = Url::fromRoute('entity.view.disable', $perameter, $options);
          $delete_url = Url::fromRoute('entity.view.delete_form', $perameter, $options);
          if (!$view->status()) {
            $links = [
              [
                '#type' => 'dropbutton',
                '#links' => [
                  [
                    'title' => $this->t('Enable'),
                    'url' => $enable_url,
                  ],
                  [
                    'title' => $this->t('Edit'),
                    'url' => $edit_url,
                  ],
                  [
                    'title' => $this->t('Duplicate'),
                    'url' => $duplicate_url,
                  ],
                  [
                    'title' => $this->t('Delete'),
                    'url' => $delete_url,
                  ],
                ],
              ],
            ];
          }
          else {
            $links = [
              [
                '#type' => 'dropbutton',
                '#links' => [
                  [
                    'title' => $this->t('Edit'),
                    'url' => $edit_url,
                  ],
                  [
                    'title' => $this->t('Duplicate'),
                    'url' => $duplicate_url,
                  ],
                  [
                    'title' => $this->t('Disable'),
                    'url' => $disable_url,
                  ],
                  [
                    'title' => $this->t('Delete'),
                    'url' => $delete_url,
                  ],
                ],
              ],
            ];
          }

          $rows[] = [
            'name' => $view->label(),
            'machine_name' => $machine_name,
            'description' => $view->get('description'),
            'oprations' => render($links),
          ];
        }
        $headers = [
          $this->t('View Name'),
          $this->t('Machine Name'),
          $this->t('Description'),
          $this->t('Oprations'),
        ];
        return [
          '#theme' => 'table',
          '#header' => $headers,
          '#rows' => $rows,
        ];
      }
      else {
        return [
          '#type' => 'markup',
          '#markup' => $this->t('No entry found for views custom tables'),
        ];
      }
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Module not installed properly, please reinstall module again.'),
    ];
  }

}
