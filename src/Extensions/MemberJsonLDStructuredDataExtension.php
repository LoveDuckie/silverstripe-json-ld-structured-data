<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use Exception;
use SilverStripe\ORM\DataExtension;

class MemberJsonLDStructuredDataExtension extends DataExtension
{
    public function generateSchemaObject() {
        return [];
    }
}