<?php

namespace Drupal\selectra_test_two\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\selectra_test_two\Entity\ProductEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProductEntityController.
 *
 *  Returns responses for Product entity routes.
 */
class ProductEntityController extends ControllerBase implements ContainerInjectionInterface {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new ProductEntityController.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(DateFormatter $date_formatter, Renderer $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a Product entity revision.
   *
   * @param int $product_entity_revision
   *   The Product entity revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($product_entity_revision) {
    $product_entity = $this->entityTypeManager()->getStorage('product_entity')
      ->loadRevision($product_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('product_entity');

    return $view_builder->view($product_entity);
  }

  /**
   * Page title callback for a Product entity revision.
   *
   * @param int $product_entity_revision
   *   The Product entity revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($product_entity_revision) {
    $product_entity = $this->entityTypeManager()->getStorage('product_entity')
      ->loadRevision($product_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $product_entity->label(),
      '%date' => $this->dateFormatter->format($product_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Product entity.
   *
   * @param \Drupal\selectra_test_two\Entity\ProductEntityInterface $product_entity
   *   A Product entity object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ProductEntityInterface $product_entity) {
    $account = $this->currentUser();
    $product_entity_storage = $this->entityTypeManager()->getStorage('product_entity');

    $langcode = $product_entity->language()->getId();
    $langname = $product_entity->language()->getName();
    $languages = $product_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $product_entity->label()]) : $this->t('Revisions for %title', ['%title' => $product_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all product entity revisions") || $account->hasPermission('administer product entity entities')));
    $delete_permission = (($account->hasPermission("delete all product entity revisions") || $account->hasPermission('administer product entity entities')));

    $rows = [];

    $vids = $product_entity_storage->revisionIds($product_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\selectra_test_two\ProductEntityInterface $revision */
      $revision = $product_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $product_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.product_entity.revision', [
            'product_entity' => $product_entity->id(),
            'product_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $product_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.product_entity.translation_revert', [
                'product_entity' => $product_entity->id(),
                'product_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.product_entity.revision_revert', [
                'product_entity' => $product_entity->id(),
                'product_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.product_entity.revision_delete', [
                'product_entity' => $product_entity->id(),
                'product_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['product_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
