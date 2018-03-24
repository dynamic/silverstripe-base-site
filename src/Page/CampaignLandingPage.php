<?php

namespace Dynamic\Base\Page;

class CampaignLandingPage extends \Page
{
    /**
     * @var array
     */
    private static $defaults = [
        'ShowInMenus' => 0,
        'ShowInSearch' => 0,
    ];

    /**
     * @return mixed
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Sidebar',
            'ElementalSidebar',
        ]);

        return $fields;
    }
}
