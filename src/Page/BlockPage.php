<?php

namespace Dynamic\Base\Page;

/**
 * Class \Dynamic\Base\Page\BlockPage
 *
 * @property string $Controls
 * @property string $Indicators
 * @property string $Transitions
 * @property string $Autoplay
 * @property int $Interval
 * @property int $ElementalAreaID
 * @method ElementalArea ElementalArea()
 * @method ManyManyList|Slide[] Slides()
 * @mixin ElementalPageExtension
 * @mixin CarouselPageExtension
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
