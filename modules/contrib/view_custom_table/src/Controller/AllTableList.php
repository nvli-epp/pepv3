<?php

namespace Drupal\view_custom_table\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Defines AllTableList class.
 */
class AllTableList extends ControllerBase {

  /**
   * Display views custom tables.
   *
   * @return array
   *   Return markup array of views custom tables.
   */
  public function content() {
    $connection = Database::getConnection();
    if ($connection->schema()->tableExists('custom_table_view_data')) {
      $all_database_connections = Database::getAllConnectionInfo();
      $query = $connection->select('custom_table_view_data', 'vd')->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender')->limit(25);
      $query->innerJoin('users_field_data', 'u', 'vd.created_by = u.uid');
      $query->fields('vd');
      $query->fields('u', ['name']);
      $results = $query->execute()->fetchAll();
      if (!empty($results)) {
        foreach ($results as $views_custom_table) {
          $delete_url = Url::fromRoute('view_custom_table.removecustomtable', ['table_name' => $views_custom_table->table_name]);
          $edit_url = Url::fromRoute('view_custom_table.editcustomtable', ['table_name' => $views_custom_table->table_name]);
          $edit_relations_url = Url::fromRoute('view_custom_table.edittablerelations', ['table_name' => $views_custom_table->table_name]);
          $views_url = Url::fromRoute('view_custom_table.customtable_views', ['table_name' => $views_custom_table->table_name]);

          $links = [
            [
              '#type' => 'dropbutton',
              '#links' => [
                [
                  'title' => $this->t('Edit'),
                  'url' => $edit_url,
                ],
                [
                  'title' => $this->t('Edit Relations'),
                  'url' => $edit_relations_url,
                ],
                [
                  'title' => $this->t('Views'),
                  'url' => $views_url,
                ],
                [
                  'title' => $this->t('Delete'),
                  'url' => $delete_url,
                ],
              ],
            ],
          ];
          $rows[] = [
            'id' => $views_custom_table->id,
            'name' => $views_custom_table->table_name,
            'database' => $all_database_connections[$views_custom_table->table_database]['default']['database'],
            'description' => $views_custom_table->description,
            'created_by' => $views_custom_table->name,
            'oprations' => render($links),
          ];
        }
        $headers = [
          $this->t('ID'),
          $this->t('Table Name'),
          $this->t('Database'),
          $this->t('Description'),
          $this->t('Created By'),
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
