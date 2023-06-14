<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Tries to find the stanford_person entity matching the related persons
 * on the Old Earth news related_people field. Does lookup by display_name
 * even though Old Earth provides SUNet ID. Currently does not generate
 * a person if not found.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_related_people",
 *   handle_multiples = true
 * )
 *
 */
class EarthRelatedPeople extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value) && is_array($value)) {
      $return_array = [];
      foreach ($value as $person) {
        if (!empty($person['display_name'])) {
          $display_name = $person['display_name'];
          $nids = \Drupal::entityTypeManager()->getStorage('node')
            ->loadByProperties(['title' => $display_name, 'type' => 'stanford_person']);
          $found = array_key_first($nids);
          if (!empty($found)) {
            $return_array[] = ['target_id' => strval($found)];
          }
        }
      }
      return $return_array;
    }
    return null;
  }

}

