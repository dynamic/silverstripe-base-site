<?php

namespace Dynamic\Base\Page;

/**
 * Class \Dynamic\Base\Page\CampaignLandingPage
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
class CampaignLandingPage extends \Page
{
    /**
     * @var string
     */
    private static $singular_name = 'Campaign Landing Page';

    /**
     * @var string
     */
    private static $plural_name = 'Campaign Landing Pages';

    /**
     * @var string
     */
    private static $description = 'Create a landing page for your marketing campaign';

    /**
     * @var string
     */
    private static $table_name = 'CampaignLandingPage';

    /**
     * @var array
     */
    private static $defaults = [
        'ShowInMenus' => 0,
        'ShowInSearch' => 0,
    ];
}
