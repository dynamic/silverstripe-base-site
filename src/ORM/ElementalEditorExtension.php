<?php

namespace Dynamic\Base\ORM;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * Class ElementalEditorExtension
 * @package Dynamic\Base\ORM
 */
class ElementalEditorExtension extends Extension
{
    /**
     * @param GridField $field
     */
    public function updateField(GridField $field)
    {
        $field->getConfig()->removeComponentsByType(GridFieldDeleteAction::class);
    }
}
