<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use SilverStripe\Core\Extension;

class JsonLDStructuredDataSiteTreeExtension extends Extension
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
