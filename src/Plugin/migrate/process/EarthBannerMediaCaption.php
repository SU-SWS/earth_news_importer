<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Currently used only for the Top Media Banner Caption/Photo Credit.
 * Input from Old Earth in which the underlying field might be one of
 * several paragraph types. Strips out HTML and returns plain text limited
 * to 255 characters.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_banner_media_caption"
 * )
 *
 */
class EarthBannerMediaCaption extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $text = "";
    if (!empty($value['field_p_responsive_image_cred'])) {
      $text = reset($value['field_p_responsive_image_cred']);
    }
    else if (!empty($value['field_p_hero_banner_photo_credit'])) {
      $text = reset($value['field_p_hero_banner_photo_credit']);
    }
    if (!empty($text)) {
      $text = MailFormatHelper::htmlToText($text);
      $text = htmlspecialchars($text);
      $text = substr($text, 0, 255);
      $text = preg_replace('/[^a-zA-Z0-9 \.\,]/','', $text);
      return $text;
    }
    return $value;
   }

}
