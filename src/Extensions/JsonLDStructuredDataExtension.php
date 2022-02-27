<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use Exception;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Config\Config;

class JsonLDStructuredDataExtension extends DataExtension
{
    use Configurable;

    private const SCHEMA_URL = "https://schema.org/";

    private static $casting = [
        'PageStructuredData' => 'HTMLFragment'
    ];

    // Invoke the recognised structured data function for any type that this extends.
    public function InvokeMetadataFunction()
    {
        if ($this->owner != null) {
            $ownerTypeName = get_class($this->owner);
        }
        if (!isset($this->owner) || !isset($this->ownerTypeName)) {
            return;
        }
        if (!method_exists($ownerTypeName, 'InjectStructuredData')) {
            return;
        }
    }

    public function PageStructuredData()
    {
        $structuredDataContainer = [];
        return $this->InjectedStructuredData($structuredDataContainer);
    }

    private static $data = [];

    /**
     * Generate the breadcrumbs based on the sitetree
     * @param type $page
     * @param type $includeHome
     * @param type $homeTitle
     */
    public static function generateBreadcrumbsFromSiteTree($page, $includeHome = true, $homeTitle = 'Home') {
        $breadcrumbs = [];
        $startingPage = $page;
        
        while ($page) {
            $breadcrumbs[] = [
                'title' => $page->Title,
                'link' => $page->AbsoluteLink()
            ];
            $page = $page->ParentID ? $page->Parent() : false;
        }
        
        if ($includeHome && $startingPage->URLSegment != 'home') {
            $breadcrumbs[] = [
                'title' => $homeTitle,
                'link' => Director::absoluteBaseURL()
            ];
        }
        
        return self::setBreadcrumbs(array_reverse($breadcrumbs));
    }

    /**
     * Set the breadcrumbs
     * Example array:
     * [
     *  [
     *      'title' => 'Home',
     *      'link' => 'https://example.com'
     *  ],
     *  [
     *      'title' => 'Blog',
     *      'link' => 'https://example.com/blog'
     *  ],
     *  [
     *      'title' => 'Blog item',
     *      'link' => 'https://example.com/blog/item'
     *  ]
     * ]
     * @param array $breadcrumbs
     */
    public static function setBreadcrumbs($breadcrumbs) {
        $structuredBreadcrumbs = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        $count = 1;
        foreach ($breadcrumbs as $breadcrumbItem) {
            $structuredBreadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $count,
                'item' => [
                    '@id' => $breadcrumbItem['link'],
                    'name' => $breadcrumbItem['title']
                ]
            ];
            $count++;
        }
        
        $breadCrumbsName = Config::inst()->get(JsonLDStructuredDataExtension::class, 'breadcrumbs_list_name');
        $breadCrumbsDescription = Config::inst()->get(JsonLDStructuredDataExtension::class, 'breadcrumbs_list_description');
        $structuredBreadcrumbs["name"] = $breadCrumbsName;
        $structuredBreadcrumbs["description"] = $$breadCrumbsDescription;
        return $structuredBreadcrumbs;
    }

    public function InjectedStructuredData(array &$structuredDataContainer)
    {
        if (!isset($structuredDataContainer)) {
            throw new Exception("The structured data container is invalid or null");
        }
        $structuredDataContainer[] = JsonLDStructuredDataExtension::generateBreadcrumbsFromSiteTree($this->owner);
        $this->owner->extend('onInjectStructuredData', $structuredDataContainer);
        for($i = 0; $i < count($structuredDataContainer); $i++) {
            $structuredDataContainer[$i]["@context"] = JsonLDStructuredDataExtension::SCHEMA_URL;
        }
        
        $jsonSerializationFlags = JSON_UNESCAPED_SLASHES;

        // Save on whitespace if we're in production. Formatting only useful for debugging purposes.
        if (Director::isTest() || Director::isDev()) {
            $jsonSerializationFlags |= JSON_PRETTY_PRINT;
        }
        $serializedJson = json_encode($structuredDataContainer, $jsonSerializationFlags);
        if (!isset($serializedJson)) {
            throw new Exception("The serialized JSON is invalid or null");
        }
        $metaDataOutput = <<< EOF
        <script type="application/ld+json">
            $serializedJson
        </script>
        EOF;

        return $metaDataOutput;
    }
}
