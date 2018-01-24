<?php

namespace Dynamic\Base\Page;

use SilverStripe\ORM\HiddenClass;
use SilverStripe\UserForms\Model\UserDefinedForm;

class BaseUserDefinedForm extends UserDefinedForm implements HiddenClass
{
    /**
     * @var array
     */
    private static $hide_ancestor =
        UserDefinedForm::class;

    /**
     * @var string
     */
    private static $table_name = 'BaseUserDefinedForm';
}