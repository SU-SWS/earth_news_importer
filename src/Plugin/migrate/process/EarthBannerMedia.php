<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Gets the Old Earth media entity id for a banner field and replaces it with
 * the media id for the imported image or file.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_banner_media"
 * )
 *
 */
class EarthBannerMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!empty($value['field_p_hero_banner_media']) ||
      !empty($value['field_p_responsive_media'])) {
      $banner_media = null;
      if (!empty($value['field_p_hero_banner_media'])) {
        $banner_media = reset($value['field_p_hero_banner_media']);
      }
      else if (!empty($value['field_p_responsive_media'])) {
        $banner_media = reset($value['field_p_responsive_media']);
      }
      if (!empty($banner_media['type']) && $banner_media['type'] === 'image'
        && !empty($banner_media['id'])) {
        $field_media = [];
        if (!empty($this->configuration['field_media'])) {
          $field_media = $row->getDestinationProperty($this->configuration['field_media']);
        }
        $target_id = EarthNewsImporterUtility::replaceFieldMediaId($banner_media['id'], $field_media);
        return ['target_id' => $target_id];
      }
      else {
        return null;
      }
    }
    else if (!empty($value['field_p_video_url'])) {
      $video_value = reset($value['field_p_video_url']);
      if (empty($video_value)) {
        return null;
      }
      $mid = EarthNewsImporterUtility::lookupMediaByProperty(
        'field_media_oembed_video.value', $video_value);
      if (!empty($mid)) {
        return ['target_id' => reset($mid)];
      }
      // Create new media entity.
      $newValues = [
        'field_media_oembed_video' => $video_value,
        'name' => $video_value,
      ];
      $embed = false;
      $target_id =
        EarthNewsImporterUtility::createNewMediaEntity('video', $newValues, $embed);
      return ['target_id' => reset($target_id)];
    }
    return null;
  }

}

