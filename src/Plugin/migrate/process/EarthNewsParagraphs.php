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

  private $field_media;
  private $embedded_media;

  protected function build_su_card($values = []) {
    $paragraph_array = [
      'type' => 'stanford_card',
    ];
    if (!empty($values['header'])) {
      $paragraph_array['su_card_header'] = $values['header'];
    }
    if (!empty($values['super_header'])) {
      $paragraph_array['su_card_super_header'] = $values['super_header'];
    }
    if (!empty($values['body'])) {
      $paragraph_array['su_card_body'] = [
        'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(
          $values['body'], $this->embedded_media),
        'format' => 'stanford_html',
      ];
    }
    if (!empty($values['link'])) {
      $paragraph_array['su_card_link'] = [
        'uri' => $values['link']['uri'],
        'title' => $values['link']['title'],
        'options' => $values['link']['options'],
      ];
    }
    if (!empty($values['media'])) {
      if (!empty($values['media']['id'])) {
        $paragraph_array['su_card_media'] = [
          'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($values['media']['id'],
            $this->field_media),
        ];
      }
    }
    return $paragraph_array;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // If there's nothing to do, do nothing.
    if (empty($value) || !is_array($value)) {
      return null;
    }

    // Get our imported media field array.
    $this->field_media = [];
    if (!empty($this->configuration['field_media'])) {
      $this->field_media = $row->getDestinationProperty($this->configuration['field_media']);
    }
    // Get our imported embedded media array.
    $this->embedded_media = [];
    if (!empty($this->configuration['embedded_media'])) {
      $this->embedded_media = $row->getDestinationProperty($this->configuration['embedded_media']);
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
              $this->embedded_media),
            'format' => 'stanford_html',
          ],
        ];
      }

      // import a SLAT paragraph
      else if (str_contains($first_key, 'field_p_slat')) {
        $values = [];
        $body_text = '';
        if (!empty($value['field_p_slat_media'])) {
          $slat_media = reset($value['field_p_slat_media']);
          if (!empty($slat_media['id'])) {
            $slat_mid = EarthNewsImporterUtility::replaceFieldMediaId($slat_media['id'],
              $this->field_media);
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
          $body_text .= reset($value['field_p_slat_description']);
        }
        if (!empty($body_text)) {
          $values['body'] = $body_text;
        }
        $header = 'Required header field';
        if (!empty($value['field_p_slat_title'])) {
          $header = $value['field_p_slat_title'];
        }
        $values['header'] = $header;
        if (!empty($value['field_p_slat_link'])) {
          $values['link'] = $value['field_p_slat_link'];
        }
        $paragraph_array = $this->build_su_card($values);
      }

      // import a Pull Quote paragraph
      else if (strpos($first_key, 'field_p_quote') !== false) {
        $values = [];
        if (!empty($value['field_p_quote_bg_img_media'])) {
          $values['media'] = reset($value['field_p_quote_bg_img_media']);
        }
        $quote_body = '';
        if (!empty($value['field_p_quote_person_img_media'])) {
          $pquote_image = reset($value['field_p_quote_person_img_media']);
          if (!empty($pquote_person['id'])) {
            $person_mid = EarthNewsImporterUtility::replaceFieldMediaId($pquote_person['id'],
              $this->field_media);
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
          $values['body'] = $quote_body;
        }
        $values['header'] = "Required card header";
        $paragraph_array = $this->build_su_card($values);
      }

      // import a Postcard paragraph.
      else if (str_contains($first_key, 'field_p_postcard')) {
        $values = [];
        if (!empty($value['field_p_postcard_body'])) {
          $values['body'] = reset($value['field_p_postcard_body']);
        }
        $header = "Required card header";
        if (!empty($value['field_p_postcard_title'])) {
          $header = [
            reset($value['field_p_postcard_title']),
          ];
        }
        $values['header'] = $header;
        if (!empty($value['field_p_postcard_more'])) {
          $values['link'] = $value['field_p_postcard_more'];
        }
        $paragraph_array = $this->build_su_card($values);
      }

      // import a stanford media caption paragraph
      else if (str_contains($first_key, 'field_p_video')) {
        $paragraph_array = [];
        if (!empty($value['field_p_video_url'])) {
          $video_url = $value['field_p_video_url'];
          $mid = EarthNewsImporterUtility::lookupMediaByProperty('field_media_oembed_video',
            $video_url);
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
            if (!empty($sdss_mid)) {
              $paragraph_array['su_media_caption_media'] = [
                'target_id' => strval($sdss_mid),
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
            $banner_card = reset($value['field_p_banner_cards']);
            $values = [];
            if (!empty($banner_card['field_highlight_card_title'])) {
              $values['header'] =
                reset($banner_card['field_p_highlight_card_title']);
            }
            if (!empty($banner_card['field_p_highlight_card_subtitle'])) {
              $values['super_header'] =
                reset($banner_card['field_p_highlight_card_subtitle']);
            }
            if (!empty($banner_card['field_p_highlight_card_desc'])) {
              $values['body'] = reset($banner_card['field_p_highlight_card_desc']);
            }
            if (!empty($banner_card['field_p_highlight_card_link'])) {
              $values['link'] = $banner_card['field_p_highlight_card_link'];
            }
            $paragraph_array = $this->build_su_card($values);
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
                  $this->field_media),
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
            $banner_card = reset($value['field_p_feat_blocks_block']);
            $values= [];
            if (!empty($banner_card['field_p_simple_block_title'])) {
              $values['header'] =
                reset($banner_card['field_p_simple_block_title']);
            }
            if (!empty($banner_card['field_p_simple_block_description'])) {
              $values['body'] =
                reset($banner_card['field_p_simple_block_description']);
            }
            if (!empty($banner_card['field_p_simple_block_link'])) {
              $values['link'] = $banner_card['field_p_simple_block_link'];
            }
            if (!empty($banner_card['field_p_simple_block_media'])) {
              $values['media'] =
                reset($banner_card['field_p_simple_block_media']);
            }
            $paragraph_array = $this->build_su_card($values);
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
      else if (str_contains($first_key, 'field_p_responsive')) {
        $paragraph_array = [
          'type' => 'stanford_media_caption',
        ];
        if (!empty($value['field_p_responsive_image_cred'])) {
          $paragraph_array['su_media_caption_caption'] = [
            'value' => EarthNewsImporterUtility::replaceEmbeddedMediaUuid(
              reset($value['field_p_responsive_image_cred']),
              $this->embedded_media
            ),
            'format' => 'stanford_html',
          ];
        }
        if (!empty($value['field_p_responsive_media'])) {
          $responsive_media = reset($value['field_p_responsive_media']);
          if (!empty($responsive_media['id'])) {
            $paragraph_array['su_media_caption_media'] = [
              'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($responsive_media['id'],
                $this->field_media),
            ];
          }
        }
      }

      // callout card
      else if (str_contains($first_key, 'field_p_callout')) {
        $values = [];
        if (!empty($value['field_p_callout_wysiwyg'])) {
          $values['body'] = reset($value['field_p_callout_wysiwyg']);
        }
        if (!empty($value['field_p_callout_more_link'])) {
          $values['link'] = $value['field_p_callout_more_link'];
        }
        if (!empty($value['field_p_callout_title'])) {
          $values['header'] = $value['field_p_callout_title'];
        }
        $paragraph_array = $this->build_su_card($values);
      }

      // filmstrip slide
      else if (str_contains($first_key, 'field_p_filmstrip')) {
        if ($first_key === "field_p_filmstrip_title") {
          $paragraph_array = [
            'type' => 'stanford_wysiwyg',
            'value' => '<h3>' .
              reset($value['field_p_filmstrip_title']) . '</h3>',
            'format' => 'stanford_html',
          ];
        }
        else if ($first_key === 'field_p_filmstrip_slide') {
          $slide = reset($value['field_p_filmstrip_slide']);
          $values = [];
          if (!empty($slide['field_p_slide_body'])) {
            $values['body'] = reset($slide['field_p_slide_body']);
          }
          if (!empty($slide['field_p_slide_link'])) {
            $values['link'] = $slide['field_p_slide_link'];
          }
          if (!empty($slide['field_p_slide_media'])) {
            $values['media'] =
              reset($slide['field_p_slide_media']);
          }
          $paragraph_array = $this->build_su_card($values);
        }
        else {
          $values = [];
          if (!empty($value['field_p_slide_body'])) {
            $values['body'] = reset($value['field_p_slide_body']);
          }
          if (!empty($value['field_p_slide_link'])) {
            $values['link'] = $value['field_p_callout_more_link'];
          }
          if (!empty($value['field_p_slide_media'])) {
            $values['media'] =
              reset($value['field_p_slide_media']);
          }
          $paragraph_array = $this->build_su_card($values);
        }
      }

      // import double film strip
      else if (str_contains($first_key, 'field_p_doub')) {
        if ($first_key === 'field_p_doub_film_cards') {
          $banner_card = reset($value['field_p_doub_film_cards']);
          $values = [];
          if (!empty($banner_card['field_s_film_card_title'])) {
            $values['header'] = reset($banner_card['field_s_film_card_title']);
          }
          if (!empty($banner_card['field_s_film_card_desc'])) {
            $values['body'] = reset($banner_card['field_s_film_card_desc']);
          }
          if (!empty($banner_card['field_s_film_card_link'])) {
            $values['link'] = $banner_card['field_s_film_card_link'];
          }
          if (!empty($banner_card['field_s_film_card_media'])) {
            $values['media'] = reset($banner_card['field_s_film_card_media']);
          }
          $paragraph_array = $this->build_su_card($values);
        }
        else {
          if (!empty($value['field_p_doub_film_title'][0])) {
            $paragraph_array = [
              'type' => 'stanford_wysiwyg',
              'su_wysiwyg_text' => [
                'value' => '<h3>' . $value['field_p_doub_film_title'][0] . '</h3>',
                'format' => 'stanford_html',
              ],
            ];
          }
        }
      }

      // banner links paragraphs
      else if (str_contains($first_key, 'field_p_link_banner')) {
        if ($first_key === 'field_p_link_banner_links') {
          if (!empty($value['field_p_link_banner_links'])) {
            $banner_card = reset($value('field_p_link_banner_links'));
            $values = [];
            if (!empty($banner_card['field_p_link_item_subtext'])) {
                  $values['header'] =
                    reset($banner_card['field_p_link_item_subtext']);
            }
            if (!empty($banner_card['field_p_link_item_text'])) {
                  $values['super_header'] =
                    reset($banner_card['field_p_link_item_text']);
            }
            if (!empty($banner_card['field_p_link_item_url'])) {
              $values['link'] = $banner_card['field_p_link_item_url'];
            }
            $paragraph_array = $this->build_su_card($values);
          }
        }
        else {
          $paragraph_array = [
            'type' => 'stanford_banner',
          ];
          if (!empty($value['field_p_link_banner_media'])) {
            $banner_media = reset($value['field_p_link_banner_media']);
            if (!empty($banner_media['id'])) {
              $paragraph_array['su_banner_image'] = [
                'target_id' => EarthNewsImporterUtility::replaceFieldMediaId($banner_media['id'],
                  $this->field_media),
              ];
            }
          }
          if (!empty($value['field_p_link_banner_title'])) {
            $paragraph_array['su_banner_header'] = reset($value['field_p_link_banner_title']);
          }
          if (!empty($value['field_p_link_banner_description'])) {
            $paragraph_array['su_sdss_banner_caption'] = [
              'value' => reset($value['field_p_link_banner_description']),
              'format' => 'stanford_html',
            ];
          }
        }
      }

      // tall filmstrip
      else if (str_contains($first_key, 'field_p_tall_filmstrip')) {
        if ($first_key === 'field_p_tall_filmstrip_cards') {
          $banner_card = reset($value['field_p_tall_filmstrip_cards']);
          $values = [];
          if (!empty($value['field_p_tall_slide_desc'])) {
            $values['body'] = reset($value['field_p_tall_slide_desc']);
          }
          if (!empty($banner_card['field_p_tall_slide_title'])) {
            $values['header'] =
              reset($banner_card['field_p_tall_slide_title']);
          }
          if (!empty($banner_card['field_p_tall_slide_subtitle'])) {
            $values['super_header'] =
              reset($banner_card['field_p_tall_slide_subtitle']);
          }
          if (!empty($banner_card['field_p_tall_slide_link'])) {
            $values['link'] = $banner_card['field_p_tall_slide_link'];
          }
          if (!empty($banner_card['field_p_tall_slide_media'])) {
            $values['media'] =
              reset($banner_card['field_p_tall_slide_media']);
          }
          $paragraph_array = $this->build_su_card($values);
        }
        else {
          $tall_text = "<div>";
          if (!empty($value['field_p_tall_filmstrip_title'][0])) {
            $tall_text .= "<h3>".$value['field_p_tall_filmstrip_title'][0] . "</h3>";
          }
          if (!empty($value['field_p_tall_filmstrip_desc'][0])) {
            $tall_text .= $value['field_p_tall_filmstrip_desc'][0];
          }
          $tall_text .= "</div>";
          $paragraph_array = [
            'type' => 'stanford_wysiwyg',
            'su_wysiwyg_text' => [
              'value' =>  $tall_text,
              'format' => 'stanford_html',
            ],
          ];
        }
      }

      // link item paragraph
      else if (str_contains($first_key, 'field_p_link_item')) {
        $values = [];
        if (!empty($value['field_p_link_item_text'])) {
          $values['header'] = reset($value['field_p_link_item_text']);
        }
        if (!empty($value['field_p_link_item_url'])) {
          $values['link'] = value['field_p_link_item_url'];
        }
        $paragraph_array = $this->build_su_card($values);
      }

      // catch any unprocessed paragraphs here - used for debugging.
      else {
        \Drupal::messenger()->addStatus("Unable to import paragraph whose first key is: " . $first_key . "\.");
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
