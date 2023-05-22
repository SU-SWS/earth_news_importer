<?php

namespace Drupal\earth_news_importer\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\earth_news_importer\EarthNewsImporterUtility;

/**
 * Extremely bespoke paragraph import from Old Earth to SDSS.
 *
 * @MigrateProcessPlugin(
 *   id = "earth_news_paragraphs"
 * )
 *
 */
class EarthNewsParagraphs extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // If there's nothing to do, do nothing.
    if (empty($value) || !is_array($value)) {
      return null;
    }

    // Get our imported media field array.
    $field_media = [];
    if (!empty($this->configuration['field_media'])) {
      $field_media = $row->getDestinationProperty($this->configuration['field_media']);
    }
    // Get our imported embedded media array.
    $embedded_media = [];
    if (!empty($this->configuration['embedded_media'])) {
      $embedded_media = $row->getDestinationProperty($this->configuration['embedded_media']);
    }

    // We will build a paragraph array to save and return to the migration API.
    $paragraph_array = [];

    // Get the first key from the imported row to determine the paragraph type.
    $first_key = array_key_first($value);

    // Only do stuff if we have stuff.
    if (!empty($first_key)) {

      // import a WYSIWYG paragraph
      if ($first_key === 'field_p_wysiwyg') {
        $paragraph_array = [
          'type' => 'stanford_wysiwyg',
          'su_wysiwyg_text' => [
            'value' =>  EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($value[$first_key]),
              $embedded_media),
            'format' => 'stanford_html',
          ],
        ];
      }

      // import a SLAT paragraph
      else if (str_contains($first_key, 'field_p_slat')) {
        $paragraph_array = [
          'type' => 'stanford_card'
        ];
        $body_text = '';
        if (!empty($value['field_p_slat_media'])) {
          $slat_media = reset($value['field_p_slat_media']);
          if (!empty($slat_media['id'])) {
            $slat_mid = EarthNewsImporterUtility::replaceFieldMediaId($slat_media['id'],
              $field_media);
            $uuid = EarthNewsImporterUtility::getMediaUuid($slat_mid);
            $caption = "";
            if (!empty($value['field_p_slat_image_caption'])) {
              $caption = "data-caption=\"" .
                reset($value['field_p_slat_image_caption']) .
                "\" ";
            }
            $align = "";
            if (!empty($value['field_p_slat_image_side'])) {
              $align = "data-align=\"";
              $side = reset($value['field_p_slat_image_side']);
              if (str_contains($side, "left")) {
                $align .= "left";
              }
              else {
                $align .= "right";
              }
              $align .= "\" ";
            }
            $body_text .= "<drupal-media " . $align . "data-entity-type=\"media\" ";
            $body_text .= "data-entity-uuid=\"" . $uuid . "\" " . $caption;
            $body_text .= "data-view-mode=\"stanford_image_large\"></drupal-media>";
          }
        }
        if (!empty($value['field_p_slat_description'])) {
          $body_text .= EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($value['field_p_slat_description']),
            $embedded_media);
        }
        if (!empty($body_text)) {
          $paragraph_array['su_card_body'] = [
              'value' => $body_text .
                EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($value['field_p_slat_description']),
                  $embedded_media),
              'format' => 'stanford_html',
          ];
        }
        $header = 'Required header field';
        if (!empty($value['field_p_slat_title'])) {
          $header = $value['field_p_slat_title'];
        }
        $paragraph_array['su_card_header'] = $header;
        if (!empty($value['field_p_slat_link'])) {
          $paragraph_array['su_card_link'] = [
            'uri' => $value['field_p_slat_link']['uri'],
            'title' => $value['field_p_slat_link']['title'],
            'options' => $value['field_p_slat_link']['options'],
          ];
        }
      }

      // import a Pull Quote paragraph
      else if (strpos($first_key, 'field_p_quote') !== false) {
        $paragraph_array = [
          'type' => 'stanford_card'
        ];
        if (!empty($value['field_p_quote_bg_img_media'])) {
          $pquote_media = reset($value['field_p_quote_bg_img_media']);
          if (!empty($pquote_media['id'])) {
            $paragraph_array['su_card_media'] = [
              'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($pquote_media['id'],
                $field_media),
            ];
          }
        }
        $quote_body = '';
        if (!empty($value['field_p_quote_person_img_media'])) {
          $pquote_person = reset($value['field_p_quote_person_img_media']);
          if (!empty($pquote_person['id'])) {
            $person_mid = EarthNewsImporterUtility::replaceFieldMediaId($pquote_person['id'],
              $field_media);
            $uuid = EarthNewsImporterUtility::getMediaUuid($person_mid);
            $quote_body .= "<drupal-media data-align=\"left\" data-entity-type=\"media\" ";
            $quote_body .= "data-entity-uuid=\"" . $uuid . "\" ";
            $quote_body .= "data-view-mode=\"stanford_image_thumb_square\"></drupal-media>";
          }
        }
        if (!empty($value['field_p_quote_text'])) {
          $quote_body .= "<em>" . reset($value['field_p_quote_text'])  . "</em>";
        }
        if (!empty($value['field_p_quote_name'])) {
          $quote_body .= "&nbsp;&#8212;&nbsp;" . reset($value['field_p_quote_name']);
        }
        if (!empty($value['field_p_quote_title'])) {
          $quote_body .= ",&nbsp;" . reset($value['field_p_quote_title']);
        }
        if (!empty($quote_body)) {
          $paragraph_array['su_card_body'] = [
            'value' => $quote_body,
            'format' => 'stanford_html',
          ];
        }
        $paragraph_array['su_card_header'] = "Required card header";
      }

      // import a Postcard paragraph.
      else if (str_contains($first_key, 'field_p_postcard')) {
        $paragraph_array = [
          'type' => 'stanford_card'
        ];
        if (!empty($value['field_p_postcard_body'])) {
          $paragraph_array['su_card_body'] = [
            'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($value['field_p_postcard_body']),
              $embedded_media),
            'format' => 'stanford_html',
          ];
        }
        if (!empty($value['field_p_postcard_media'])) {
          $postcard_media = reset($value['field_p_postcard_media']);
          if (!empty($postcard_media['id'])) {
            $paragraph_array['su_card_media'] = [
              'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($postcard_media['id'],
                $field_media),
            ];
          }
        }
        $header = "Required card header";
        if (!empty($value['field_p_postcard_title'])) {
          $header = [
            reset($value['field_p_postcard_title']),
          ];
        }
        $paragraph_array['su_card_header'] = $header;
        if (!empty($value['field_p_postcard_more'])) {
          $paragraph_array['su_card_link'] = [
            'uri' => $value['field_p_postcard_more']['uri'],
            'title' => $value['field_p_postcard_more']['title'],
            'options' => $value['field_p_postcard_more']['options'],
          ];
        }
      }

      // import a stanford media caption paragraph
      else if (str_contains($first_key, 'field_p_video')) {
        $paragraph_array = [];
        if (!empty($value['field_p_video_url'])) {
          $video_url = $value['field_p_video_url'];
          $mid = EarthNewsImporterUtility::lookupMediaByProperty('field_media_oembed_video',
            $video_url, $value);
          if (empty($mid)) {
            $name = "";
            if (!empty($value['field_p_video_title'])) {
              $name = reset($value['field_p_video_title']);
            }
            $media_values = [
              'field_media_oembed_video' => reset($video_url),
              'name' => $name,
            ];
            $mid = EarthNewsImporterUtility::createNewMediaEntity('video', $media_values);
          }
          if (!empty($mid)) {
            $sdss_mid = reset($mid);
            if (!empty($sdss_mid['sdss-mid'])) {
              $paragraph_array['su_media_caption_media'] = [
                'target_id' => strval($sdss_mid['sdss-mid']),
              ];
            }
          }
        }
        $caption = "";
        if (!empty($value['field_p_video_title'])) {
          $caption = "<p>" . reset($value['field_p_video_title']) . "</p>";
        }
        if (!empty($value['field_p_video_subheading'])) {
          $caption .= "<p>" . reset($value['field_p_video_subheading']) . "</p>";
        }
        if (!empty($caption)) {
          $paragraph_array['su_media_caption_caption'] = [
            'value' => $caption,
            'format' => 'stanford_html',
          ];
        }
        if (!empty($paragraph_array)) {
          $paragraph_array['type'] = 'stanford_media_caption';
        }
      }

      // import stanford banner and cards
      else if (str_contains($first_key, 'field_p_banner')) {
        if ($first_key === 'field_p_banner_cards') {
          if (!empty($value['field_p_banner_cards'])) {
            foreach ($value['field_p_banner_cards'] as $banner_card) {
              if (!empty($banner_card)) {
                $paragraph_array = [
                  'type' => 'stanford_card',
                ];
                if (!empty($banner_card['field_highlight_card_title'])) {
                  $paragraph_array['su_card_header'] =
                    reset($banner_card['field_p_highlight_card_title']);
                }
                if (!empty($banner_card['field_p_highlight_card_subtitle'])) {
                  $paragraph_array['su_card_super_header'] =
                    reset($banner_card['field_p_highlight_card_subtitle']);
                }
                if (!empty($banner_card['field_p_highlight_card_desc'])) {
                  $paragraph_array['su_card_body'] = [
                    'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($banner_card['field_p_highlight_card_desc']),
                      $embedded_media),
                    'format' => 'stanford_html',
                  ];
                }
                if (!empty($banner_card['field_p_highlight_card_link'])) {
                  $paragraph_array['su_card_link'] = [
                      'uri' => $banner_card['field_p_highlight_card_link']['uri'],
                      'title' => $banner_card['field_p_highlight_card_link']['title'],
                      'options' => $banner_card['field_p_highlight_card_link']['options'],
                    ];
                }
              }
            }
          }
        }
       else {
          $paragraph_array = [
            'type' => 'stanford_banner',
          ];
          if (!empty($value['field_p_banner_media'])) {
            $banner_media = reset($value['field_p_banner_media']);
            if (!empty($banner_media['id'])) {
              $paragraph_array['su_banner_image'] = [
                'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($banner_media['id'],
                  $field_media),
              ];
            }
          }
          if (!empty($value['field_p_banner_title'])) {
            $paragraph_array['su_banner_header'] = reset($value['field_p_banner_title']);
          }
        }
      }

      // import stanford feature blocks paragraphs
      else if (str_contains($first_key, 'field_p_feat_blocks')) {
        if ($first_key === 'field_p_feat_blocks_block') {
          if (!empty($value['field_p_feat_blocks_block'])) {
            foreach ($value['field_p_feat_blocks_block'] as $banner_card) {
              if (!empty($banner_card)) {
                $paragraph_array = [
                  'type' => 'stanford_card',
                ];
                if (!empty($banner_card['field_p_simple_block_title'])) {
                  $paragraph_array['su_card_header'] =
                    reset($banner_card['field_p_simple_block_title']);
                }
                if (!empty($banner_card['field_p_simple_block_description'])) {
                  $paragraph_array['su_card_body'] = [
                    'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(reset($banner_card['field_p_simple_block_description']),
                      $embedded_media),
                    'format' => 'stanford_html',
                  ];
                }
                if (!empty($banner_card['field_p_simple_block_link'])) {
                  $paragraph_array['su_card_link'] = [
                    'uri' => $banner_card['field_p_simple_block_link']['uri'],
                    'title' => $banner_card['field_p_simple_block_link']['title'],
                    'options' => $banner_card['field_p_simple_block_link']['options'],
                  ];
                }
                if (!empty($banner_card['field_p_simple_block_media'])) {
                  $card_media = reset($banner_card['field_p_simple_block_media']);
                  if (!empty($card_media['id'])) {
                    $paragraph_array['su_card_media'] = [
                      'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($card_media['id'],
                        $field_media),
                    ];
                  }
                }

              }
            }
          }
        }
        else {
          $paragraph_array = [
            'type' => 'stanford_banner',
          ];
          if (!empty($value['field_p_feat_blocks_super_head'])) {
            $paragraph_array['su_banner_header'] = reset($value['field_p_feat_blocks_super_head']);
          }
        }
      }

      // import section header
      else if (str_contains($first_key, 'field_p_section_header')) {
        $paragraph_array = [
          'type' => 'stanford_banner',
        ];
        if (!empty($value['field_p_section_header_title'])) {
          $paragraph_array['su_banner_header'] = reset($value['field_p_section_header_title']);
        }
        if (!empty($value['field_p_section_header_desc'])) {
          $paragraph_array['su_banner_body'] = [
            'value' => reset($value['field_p_section_header_desc']),
            'format' => 'stanford_html',
          ];
        }
      }

      // responsive image
      else if (str_contains($first_key, 'field_p_responsive_image')) {
        $paragraph_array = [
          'type' => 'stanford_media_caption',
        ];
        if (!empty($value['field_p_responsive_image_cred'])) {
          $paragraph_array['su_media_caption_caption'] = [
            'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(
              reset($value['field_p_responsive_image_cred']),
              $embedded_media
            ),
            'format' => 'stanford_html',
          ];
        }
        if (!empty($value['field_p_responsive_media'])) {
          $responsive_media = reset($value['field_p_responsive_media']);
          if (!empty($responsive_media['id'])) {
            $paragraph_array['su_media_caption_media'] = [
              'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($responsive_media['id'],
                $field_media),
            ];
          }
        }
      }

      // callout card
      else if (str_contains($first_key, 'field_p_callout')) {
        $paragraph_array = [
          'type' => 'stanford_card',
        ];
        if (!empty($value['field_p_callout_wysiwyg'])) {
          $paragraph_array['su_card_body'] = [
            'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(
              reset($value['field_p_callout_wysiwyg']),
              $embedded_media
            ),
            'format' => 'stanford_html',
          ];
        }
        if (!empty($value['field_p_callout_more_link'])) {
          $paragraph_array['su_card_link'] = [
            'uri' => $value['field_p_callout_more_link']['uri'],
            'title' => $value['field_p_callout_more_link']['title'],
            'options' => $value['field_p_callout_more_link']['options'],
          ];
        }
        if (!empty($value['field_p_callout_title'])) {
          $paragraph_array['su_card_header'] =
            reset($value['field_p_callout_title']);
        }
      }

      // catch any unprocessed paragraphs here - used for debugging.
      else {
        $xyz = 1;
      }

      // If we have a new paragraph array, save it and return its id.
      if (!empty($paragraph_array)) {
        $paragraph = Paragraph::create($paragraph_array);
        $paragraph->save();
        return [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }
    return null;
  }

}
