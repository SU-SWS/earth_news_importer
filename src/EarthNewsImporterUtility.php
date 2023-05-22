<?php

namespace Drupal\earth_news_importer;

/**
 * Provides shared utility functions to the different migration process plugins.
 */
class EarthNewsImporterUtility {

  /**
   * Replace an embedded media uuid from earth with one for an imported image.
   *
   * @param string $text
   *   Body text possibly containing an Old Earth media uuid.
   * @param array $embedded_media
   *   Array of embedded media being imported for the current node.
   *
   * @return string
   *   Original or updated text string.
   */
  public static function replaceEmbeddedMediaUuid($text = '', $embedded_media = []) {

    // If no embedded media, do nothing.
    if (empty($embedded_media)) {
      return $text;
    }
    if (!empty($text)) {
      // If there is text, then for each embedded media item in the node,
      // find it in the text and replace it with the new media uuid.
      foreach ($embedded_media as $media_item) {
        foreach ($media_item as $old => $media_array) {
          if (str_contains($text, $old)) {
            if (!empty($media_array['sdss-uuid'])) {
              $text = str_replace($old, $media_array['sdss-uuid'], $text);
            }
          }
        }
      }

      // Replace any Old Earth data-view-modes in the text with stanford_image_large.
      $pattern = '/data-view-mode="([^"]+)"/';
      $replacementString = 'data-view-mode="stanford_image_large"';
      $text = preg_replace($pattern, $replacementString, $text);
    }
    return $text;
  }

  /**
   * Replace a media field id from earth with one for an imported image.
   *
   * @param string $earth_mid
   *   Old earth mid of media entity in this node.
   * @param array $field_media
   *   Array of field media entities imported for this node..
   *
   * @return string
   *   New media id.
   */
  public static function replaceFieldMediaId($earth_mid = '', $field_media = []) {
    // See if we have anything to do.
    if (!empty($earth_mid) && !empty($field_media)) {
      // For each field media entity imported for the node, return a match.
      foreach ($field_media as $media_item) {
        if (!empty($media_item[$earth_mid])) {
          $sdss_mid = $media_item[$earth_mid]['sdss-mid'];
          return strval($sdss_mid);
        }
      }
    }
    // Return the original if not found.
    return $earth_mid;
  }

  /**
   * Return the UUID for a given media entity id.
   *
   * @param string $mediaId
   *   Media entity id for which to lookup UUID.
   *
   * @return string
   *   UUID.
   */
  public static function getMediaUuid($mediaId = "") {
    // If the given media entity id is not empty, load it and return its UUID.
    $uuid = "";
    if (!empty($mediaId)) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->load($mediaId);
      if (!empty($media)) {
        $uuid = $media->uuid();
      }
    }
    return $uuid;
  }

  /**
   * Create a new Media Entity.
   *
   * @param string $bundle
   *   Whether the media entity is an image, a video url, or a file.
   * @param array $fieldValues
   *   Array of fields to be included in the media entity.
   * @param array $value
   *   Array of import values for the node, where we might find an embed uuid.
   * @param boolean $embed
   *   Tells us whether to look for an embed code.
   *
   * @return array
   *   Information needed about the new media entity.
   */
  public static function createNewMediaEntity($bundle = "", $fieldValues = [], $value=[], $embed = false) {

    // Make sure we have something to do.
    if (!empty($bundle) && !empty($fieldValues)) {
      // Create the media entity and populate its fields.
      $storage = \Drupal::entityTypeManager()->getStorage('media');
      $media_entity = $storage->create(['bundle' => $bundle]);
      foreach ($fieldValues as $fieldName => $fieldValue) {
        $media_entity->get($fieldName)->setValue($fieldValue);
      }
      $media_entity->save();
      // If this is an embedded entity, return its old Earth and new UUIDs
      if ($embed && !empty($value['embed'])) {
        $uuid = $media_entity->uuid();
        return [
          $value['embed'] => [
            'sdss-mid' => $media_entity->id(),
            'sdss-uuid' => $uuid
          ]
        ];
      }
      // Otherwise just return its new entity id.
      else {
        return [
          $value['mid'] => [
            'sdss-mid' => $media_entity->id(),
          ]
        ];
      }
    }
    return null;
  }

  /**
   * Lookup a Media Entity by some property.
   *
   * @param string $prop_name
   *   Name of the property we are searching by.
   * @param string $prop_value
   *   Value of the property.
   * @param array $value
   *   Array of import values for the node, where we might find an embed uuid.
   * @param boolean $embed
   *   Tells us whether to look for an embed code.
   *
   * @return array
   *   Information needed about the new media entity.
   */
  public static function lookupMediaByProperty($prop_name = "", $prop_value = "", $value = [], $embed = false) {
    if (!empty($prop_name) && !empty($prop_value)) {
      $mids = \Drupal::entityTypeManager()->getStorage('media')
        ->loadByProperties([$prop_name => $prop_value]);
      $existing_mid = array_key_first($mids);
      if (!empty($existing_mid)) {
        if ($embed && !empty($value['embed'])) {
          /** @var \Drupal\media\Entity\Media $mediaObj */
          $mediaObj = $mids[$existing_mid];
          $newuuid = $mediaObj->uuid();
          return [
            $value['embed'] => [
              'sdss-mid' => $existing_mid,
              'sdss-uuid' => $newuuid,
            ]
          ];
        }
        else {
          return [
            $value['mid'] => [
              'sdss-mid' => $existing_mid,
            ]
          ];
        }
      }
    }
    return null;
  }

}


