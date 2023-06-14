use Drupal\Tests\UnitTestCase;

/**
 * Tests presence of this module.
 *
 * @group earth_news_importer
 */
class EarthNewsImporterTest extends UnitTestCase {

  public function testModuleExists() {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('earth_news_importer'));
  }

}

