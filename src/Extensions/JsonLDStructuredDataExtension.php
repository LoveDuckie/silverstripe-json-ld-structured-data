<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredDataExtension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Blog\Model\BlogPost;

use SilverStripe\Control\Director;

use Portfolio\Models\AboutPage;
use Portfolio\Models\ContactPage;
use Portfolio\Models\EventPage;
use Portfolio\Models\ProjectPage;

use SilverStripe\ORM\FieldType\DBDateTime;

use SilverStripe\i18n\i18n;

class JsonLDStructuredDataExtension extends DataExtension
{
    private const CONFIG_CLASS_NAME = "LoveDuckie\\SilverStripe\\JsonLDStructuredDataExtension";
    private const SCHEMA_URL = "https://schema.org/";

    public function PageStructuredData() {
        $structuredDataContainer = array();
        $structuredDataContainer["@context"] = JsonLDStructuredDataExtension::SCHEMA_URL;
        return $structuredDataContainer;
    }

    // Parent function for getting relevant encoding information
    public function InjectJsonLDStructuredData()
    {
        if ($structuredDataContainer == null || !isset($structuredDataContainer)) {
            throw new Exception("The structured data container was not defined.");
        }

        $siteConfig = SiteConfig::get();
        $serializedJson = json_encode($structuredDataContainer);

        $metaDataOutput = <<< EOF
        <script type="application/ld+json">
            $serializedJson
        </script>
        EOF;

        return $metaDataOutput;
    }
}
