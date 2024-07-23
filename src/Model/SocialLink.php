<?php

namespace Dynamic\Base\Model;

use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class SocialLink
 *
 * @property string $SocialChannel
 */
class SocialLink extends ExternalLink implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = 'Social Link';

    /**
     * @var string
     */
    private static $plural_name = 'Social Links';

    /**
     * @var string
     */
    private static $table_name = 'SocialLink';

    /**
     * @var int
     */
    private static int $priority = 1;

    /**
     * @var string
     */
    private static string $icon = 'font-icon-torsos-all';

    /**
     * @var array
     */
    private static array $db = [
        'SocialChannel' => 'Varchar',
    ];

    /**
     * @var array|string[]
     */
    private static array $summary_fields = [
        'SocialChannelName' => 'Social Channel',
    ];

    /**
     * @var array|string[]
     */
    private static array $defaults = [
        'OpenInNew = true',
    ];

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName([
                'SocialChannel',
            ]);

            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create(
                    'SocialChannel',
                    'Social Channel',
                    $this->getSocialChannels()
                )
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @return string[]
     */
    public function getSocialChannels(): array
    {
        $channels = [
            'facebook' => 'Facebook',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'instagram' => 'Instagram',
        ];

        $this->extend('updateSocialChannels', $channels);

        return $channels;
    }

    /**
     * @return CompositeValidator
     */
    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(RequiredFields::create(['SocialChannel', 'ExternalUrl']));
        return $validator;
    }

    /**
     * @return string|null
     */
    public function getSocialChannelName(): ?string
    {
        if (in_array($this->SocialChannel, $this->getSocialChannels())) {
            return $this->SocialChannel;
        }

        return null;
    }


    /**
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t(__CLASS__ . '.LINKLABEL', 'Link to Social Channel');
    }

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('Social_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('Social_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('Social_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'Social_CRUD' => 'Create, Update and Delete a Social Link',
        ];
    }
}
