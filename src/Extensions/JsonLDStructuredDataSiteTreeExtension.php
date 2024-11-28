<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use SilverStripe\ORM\DataExtension;

class JsonLDStructuredDataSiteTreeExtension extends DataExtension
{
    public function onInjectStructuredData(&$structuredDataContainer)
    {
        if ($structuredDataContainer && $this->owner) {
            if ($this->owner->hasMethod('updateStructuredData')) {
                $this->owner->updateStructuredData($structuredDataContainer);
            }
        }
    }
}
