<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Gets the Old Earth media entity id for a media field and replaces it with
 * the media id for the imported image or file.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_media_field_id"
 * )
 *
 */
class EarthMediaFieldId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $field_media = [];
    if (!empty($this->configuration['field_media'])) {
      $field_media = $row->getDestinationProperty($this->configuration['field_media']);
    }
    if (empty($value['id'])) {
      return null;
    }
    return EarthNewsImporterUtility::replaceFieldMediaId($value['id'], $field_media);
  }

}

