<?php

namespace Dynamic\Base\ORM;

use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;

class BlogPostDataExtension extends DataExtension
{
    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'SubTitle',
            'CustomSummary',
        ));

        $fields->insertAfter(TextField::create('SubTitle', 'Sub Title'), 'Title');

        $featured = $fields->dataFieldByName('FeaturedImage')
            ->setFolderName('Uploads/Blog')
        ;
        $fields->insertBefore($featured, 'Content');
    }

    /**
     * @return DataList
     */
    public function getRelatedPosts()
    {
        $posts = BlogPost::get()
            ->filter(array(
                'ParentID' => $this->owner->ParentID,
            ))
            ->exclude('ID', $this->owner->ID)
        ;

        if ($this->owner->Tags()->count() > 0) {
            $posts->filterAny(array(
                'Tags.ID' => $this->owner->Tags()->map('ID', 'ID')->toArray(),
            ));
        }

        return $posts;
    }
}
