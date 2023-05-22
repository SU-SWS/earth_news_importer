<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Currently used only for the DEK field. Replaces any embedded UUID from
 * Old Earth with uuid for imported media. Problematic since SDSS DEK only
 * allows stanford_minimal_html while some Old Earth summary fields have
 * full HTML.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_embedded_media_body"
 * )
 *
 */
class EarthEmbeddedMediaBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $embedded_media = [];
    // Retrieve the dek text already processed from either summary or teaser fields.
    if (!empty($this->configuration['dek'])) {
      $value = $row->getDestinationProperty($this->configuration['dek']);
    }
    // Get the emmbedded media array if it exists.
    if (!empty($this->configuration['embedded_media'])) {
      $embedded_media = $row->getDestinationProperty($this->configuration['embedded_media']);
    }
    // Replace any Old Earth UUIDs with new ones.
    return EarthNewsImporterUtility::replaceEmbeddedMediaUuid($value, $embedded_media);
   }

}
