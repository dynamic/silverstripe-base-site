<?php

namespace Dynamic\Base\Test;

use DNADesign\Elemental\Models\ElementContent;
use DNADesign\Elemental\Models\ElementalArea;
use Dynamic\Base\Page\HomePage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class HomePageTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(HomePage::class, 'default');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     * The module's own layout template must render the blocks held in the
     * `ElementalHomePage` relation (issue #219: it printed a variable that the
     * class never defined, so no blocks were ever output).
     */
    public function testTemplateRendersElementalHomePageBlocks(): void
    {
        $this->logInAs('admin');
        $page = $this->objFromFixture(HomePage::class, 'default');
        $page->ElementalHomePageID = $this->makeAreaWithContentBlock(
            'elemental-home-page-marker'
        )->ID;
        $page->write();

        $output = $this->renderLayout($page);

        $this->assertStringContainsString(
            'elemental-home-page-marker',
            $output,
            'The HomePage layout template renders blocks from the ElementalHomePage area'
        );
    }

    /**
     * An empty `ElementalHomePage` area must render cleanly rather than error.
     */
    public function testTemplateRendersEmptyElementalHomePageArea(): void
    {
        $this->logInAs('admin');
        $page = $this->objFromFixture(HomePage::class, 'default');
        $area = ElementalArea::create();
        $area->OwnerClassName = HomePage::class;
        $area->write();
        $page->ElementalHomePageID = $area->ID;
        $page->write();

        $output = $this->renderLayout($page);

        $this->assertStringContainsString(
            'Welcome To My Website',
            $output,
            'The HomePage layout template still renders the page title with an empty block area'
        );
        $this->assertStringNotContainsString(
            'elemental-home-page-marker',
            $output,
            'An empty ElementalHomePage area renders no block markup'
        );
    }

    /**
     * Render the page through the module's own layout template.
     *
     * @param HomePage $page
     * @return string
     */
    private function renderLayout(HomePage $page): string
    {
        return (string)$page->renderWith(['type' => 'Layout', HomePage::class]);
    }

    /**
     * Build a saved elemental area containing a single content block.
     *
     * @param string $html
     * @return ElementalArea
     */
    private function makeAreaWithContentBlock(string $html): ElementalArea
    {
        $area = ElementalArea::create();
        $area->OwnerClassName = HomePage::class;
        $area->write();

        $block = ElementContent::create();
        $block->BlockTitle = 'Issue 219 block';
        $block->HTML = $html;
        $block->ParentID = $area->ID;
        $block->Sort = 1;
        $block->write();

        return $area;
    }
}
