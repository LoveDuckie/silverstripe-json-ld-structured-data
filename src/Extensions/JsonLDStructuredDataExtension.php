<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

class JsonLDStructuredDataExtension extends DataExtension
{
    private const CONFIG_CLASS_NAME = "LoveDuckie\\SilverStripe\\JsonLDStructuredDataExtension";
    private const SCHEMA_URL = "https://schema.org/";

    // Attempt to invoke the static function on the type of the owner object if it exists.
    public function InvokeMetadataFunction()
    {
        if ($this->owner != null) {
            $ownerTypeName = get_class($this->owner);
        }
        if (!isset($this->owner) || !isset($this->ownerTypeName)) {
            return;
        }
        if (!method_exists($ownerTypeName,'InjectStructuredData')) {
            return;
        }
    }

    // Use this for injecting stuctured data into the page
    public function PageStructuredData()
    {
        $structuredDataContainer = array();
        if ($structuredDataContainer == null || !isset($structuredDataContainer)) {
            throw new Exception("The structured data container was not defined.");
        }

        $structuredDataContainer["@context"] = JsonLDStructuredDataExtension::SCHEMA_URL;
        return $structuredDataContainer;
    }

    // Parent function for getting relevant encoding information
    public function InjectJsonLDStructuredData(array $structuredDataContainer)
    {
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
