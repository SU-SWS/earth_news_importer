<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Old Earth News has both a Teaser and a Summary Field. For most nodes,
 * either one or the other is used, or they are both the same. Some, however,
 * are both used and different. Here we choose the summary field if used,
 * otherwise we use the teaser field if used. The result is stored and
 * processed later by the earth_embedded_media_body plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_teaser_or_summary"
 * )
 *
 */
class EarthTeaserOrSummary extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $value = "";
    $teaser = "";
    $summary = "";
    if (!empty($this->configuration['earth_teaser'])) {
      $teaser = $row->getSourceProperty($this->configuration['earth_teaser']);
    }
    if (!empty($this->configuration['earth_summary'])) {
      $summary = $row->getSourceProperty($this->configuration['earth_summary']);
    }
    if (!empty($summary)) {
      $value = $summary;
    }
    else if (!empty($teaser)) {
      $value = $teaser;
    }
    return $value;
  }

}
