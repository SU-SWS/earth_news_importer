<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Not trusting existing paragraph migration code to clean up after itself
 * if a migration is rolled back or a node is deleted, I use this to
 * delete any existing paragraphs for a node when it is re-migrated. Make sure
 * this plugin is run before anything else!
 *
 * @MigrateProcessPlugin(
 *   id = "earth_news_paragraphs_delete"
 * )
 *
 */
class EarthNewsParagraphsDelete extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // See if the Old Earth News node being migrated has already been done.
    $nid = null;
    $idMap = $row->getIdMap();
    if (!empty($idMap['destid1'])) {
      $nid = $idMap['destid1'];
    }

    // If we have an existing node, delete its paragraphs.
    if (!empty($nid)) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!empty($node)) {
        // Paragraphs are all in the su_news_components field.
        $components = $node->get('su_news_components')->getValue();
        if (!empty($components)) {
          foreach ($components as $para) {
            if (!empty($para['target_id']) && !empty($para['target_revision_id'])) {
              $query = \Drupal::entityQuery('paragraph')
                ->condition('id', $para['target_id'])
                ->condition('revision_id', $para['target_revision_id']);
              $pids = $query->execute();
              $paragraphs = \Drupal\paragraphs\Entity\Paragraph::loadMultiple($pids);
              /** @var \Drupal\paragraphs\Entity $paragraph */
              foreach ($paragraphs as $paragraph) {
                $paragraph->delete();
              }
            }
          }
          $node->su_news_components = null;
          $node->save();
        }
      }
    }

    return $value;
  }

}
