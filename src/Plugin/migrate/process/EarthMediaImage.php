<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\migrate_file\Plugin\migrate\process\FileImport;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Imports a media entity from Old Earth, mostly used for images, but also
 * for video links. May be used for files also in the near future.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_media_image"
 * )
 */
class EarthMediaImage extends FileImport {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin) {
    $configuration += [
      'title' => NULL,
      'alt' => NULL,
      'width' => NULL,
      'height' => NULL,
      'destination_property_entity' => NULL,
      'destination_property_bundle' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $stream_wrappers, $file_system, $download_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value['type'])) {
      return NULL;
    }

    // Are we importing an image?
    if ($value['type'] == 'image') {
      // Get the URL to the image or return null if empty
      if (empty($value['url']) || empty($value['name'])) {
        return NULL;
      }

      // Check if we already have the image and return it if we do.
      $file_name = $value['name'];
      $file_name = str_replace(" ", "%20", $file_name);
      $file_info = pathinfo($value['url']);
      if (!empty($file_info['basename']) && $file_info['basename'] !== $file_name) {
        $file_name = $file_info['basename'];
      }
      $mid = EarthNewsImporterUtility::lookupMediaByProperty('name', $file_name, $value);
      if (!empty($mid)) {
        return $mid;
      }

      // The parent will download the image (if necessary) and get its fid.
      $this->configuration['id_only'] = FALSE;
      $url = $value['url']; //str_replace("earth.stanford.edu", "se3-stage.stanford.edu", $value['url']);
      $newvalue = parent::transform($url, $migrate_executable, $row,
        $destination_property);
      // Add the image field specific sub fields.
      foreach (['title', 'alt', 'width', 'height'] as $key) {
        $property = NULL;
        if (!empty($value[$key])) {
          $property = $value[$key];
        }
        if ($key == 'title' && empty($property)) {
          $property = $value['name'];
        }
        if (!empty($property)) {
          $newvalue[$key] = $property;
        }
      }

      // Create new media entity.
      $newValues = ['field_media_image' => $newvalue];
      $embed = !empty($this->configuration['embed']);
      return EarthNewsImporterUtility::createNewMediaEntity('image', $newValues, $value, $embed);
    }

    // Otherwise are we importing video?
    else if ($value['type'] == 'video') {
      // Get the URL to the image or return null if empty
      if (empty($value['value']) || empty($value['name'])) {
        return NULL;
      }

      // Check if we already have the video and return its mid if we do.
      $video_value = $value['value'];
      $mid = EarthNewsImporterUtility::lookupMediaByProperty('field_media_oembed_video.value',
        $video_value, $value);
      if (!empty($mid)) {
        return $mid;
      }

      // Create new media entity.
      $newValues = [
        'field_media_oembed_video' => $video_value,
        'name' => $value['name'],
      ];
      $embed = !empty($this->configuration['embed']);
      return EarthNewsImporterUtility::createNewMediaEntity('video', $newValues, $value, $embed);

    // Otherwise, do nothing, but we have a breakpoint here in debugging to catch other media types.
    } else {
      $xyz = 1; // some other media
    }
  }

}