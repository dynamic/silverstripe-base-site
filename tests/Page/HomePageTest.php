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
     * HTML placed in the content block so its presence (or absence) in rendered
     * markup can be asserted.
     */
    private const BLOCK_MARKER = 'elemental-home-page-marker';

    /**
     * Opening markup of DNADesign\Elemental\Layout\ElementHolder.ss, i.e. proof that a
     * block was actually rendered as an element rather than that some string survived.
     */
    private const ELEMENT_WRAPPER = 'class="element ';

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
        $page->ElementalHomePageID = $this->makeAreaWithContentBlock(self::BLOCK_MARKER)->ID;
        $page->write();

        $output = $this->renderLayout($page);

        $this->assertStringContainsString(
            self::BLOCK_MARKER,
            $output,
            'The HomePage layout template renders blocks from the ElementalHomePage area'
        );
        $this->assertStringContainsString(
            self::ELEMENT_WRAPPER,
            $output,
            'The block is rendered through elemental\'s element wrapper, not just as loose text'
        );
    }

    /**
     * An empty `ElementalHomePage` area must render the page cleanly, with no element
     * wrapper markup. The control render above the negative assertion keeps that
     * assertion falsifiable: the same page, rendered with one block, does emit the
     * wrapper, so its absence below is a statement about the empty area.
     */
    public function testTemplateRendersEmptyElementalHomePageArea(): void
    {
        $this->logInAs('admin');
        $page = $this->objFromFixture(HomePage::class, 'default');

        // Control: the same page with one block must emit the element wrapper, so the
        // negative assertion below is reachable rather than vacuous.
        $page->ElementalHomePageID = $this->makeAreaWithContentBlock(self::BLOCK_MARKER)->ID;
        $page->write();
        $control = $this->renderLayout($page);
        $this->assertStringContainsString(
            self::ELEMENT_WRAPPER,
            $control,
            'Precondition: a HomePage with a block renders an element wrapper'
        );
        $this->assertStringContainsString(self::BLOCK_MARKER, $control);

        $page->ElementalHomePageID = $this->makeEmptyArea()->ID;
        $page->write();

        $output = $this->renderLayout($page);

        $this->assertStringContainsString(
            'Welcome To My Website',
            $output,
            'The HomePage layout template still renders the page title with an empty block area'
        );
        $this->assertStringNotContainsString(
            self::ELEMENT_WRAPPER,
            $output,
            'An empty ElementalHomePage area renders no element markup'
        );
        $this->assertStringNotContainsString(
            self::BLOCK_MARKER,
            $output,
            'Switching to an empty area renders none of the previous area\'s blocks'
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
        $area = $this->makeEmptyArea();

        $block = ElementContent::create();
        $block->BlockTitle = 'Issue 219 block';
        $block->HTML = $html;
        $block->ParentID = $area->ID;
        $block->Sort = 1;
        $block->write();

        return $area;
    }

    /**
     * Build a saved, empty elemental area owned by HomePage.
     *
     * @return ElementalArea
     */
    private function makeEmptyArea(): ElementalArea
    {
        $area = ElementalArea::create();
        $area->OwnerClassName = HomePage::class;
        $area->write();

        return $area;
    }
}
