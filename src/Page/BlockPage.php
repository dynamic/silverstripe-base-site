<?php

namespace Dynamic\Base\Page;

/**
 * Class \Dynamic\Base\Page\BlockPage
 *
 * @property int $ElementalAreaID
 * @property int $HeaderImageID
 * @method ElementalArea ElementalArea()
 * @method HeaderImage HeaderImage()
 * @mixin HeaderImageExtension
 * @mixin ElementalPageExtension
 */
class BlockPage extends \Page
{
    /**
     * @var string
     */
    private static $singular_name = 'Block Page';

    /**
     * @var string
     */
    private static $plural_name = 'Block Pages';

    /**
     * @var string
     */
    private static $description = 'Flexible layout page created using content blocks';

    /**
     * @var string
     */
    private static $table_name = 'BlockPage';
}
