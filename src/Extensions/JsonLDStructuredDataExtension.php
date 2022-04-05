<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use Exception;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Controllers\ContentController;

use SilverStripe\CMS\Model\SiteTree;

class JsonLDStructuredDataExtension extends DataExtension
{
    use Configurable;

    private const SCHEMA_URL = "https://schema.org/";

    private static $casting = [
        'PageStructuredData' => 'HTMLFragment'
    ];

    public function PageStructuredData()
    {
        $structuredDataContainer = [];
        return $this->InjectedStructuredData($structuredDataContainer);
    }

    public static function generateBreadCrumbsFromController($controller)
    {
        if (!($controller instanceof ContentController)) {
            throw new Exception("The object specified is not a controller");
        }

        $breadCrumbs = [];
        if ($controller->hasMethod('generateBreadCrumbs')) {
            $controller->generateBreadCrumbs($breadCrumbs);
            if (!is_array($breadCrumbs)) {
                throw new Exception("The breadCrumbs specified are invalid or null");
            }

            return $breadCrumbs;
        }

        return null;
    }

    public static function generateBreadCrumbs($pageOrController)
    {
        if (!isset($pageOrController)) {
            throw new Exception("The page or controller instance was not defined.");
        }

        $breadCrumbs = null;
        if ($pageOrController instanceof ContentController) {
            $breadCrumbs = static::generateBreadCrumbsFromController($pageOrController);
        } else if ($pageOrController instanceof SiteTree) {
            $breadCrumbs = static::generateBreadCrumbsFromSiteTree($pageOrController);
        }

        return $breadCrumbs;
    }

    public static function generateBreadCrumbsFromSiteTree($page, $includeHome = true, $homeTitle = 'Home')
    {
        $breadCrumbs = [];
        $startingPage = $page;

        if ($startingPage->hasMethod('generateBreadCrumbs')) {
            $startingPage->generateBreadCrumbs($breadCrumbs);
        }

        while ($page) {
            $pageLink = $page->AbsoluteLink();
            $pageTitle = $page->Title;
            $breadCrumbs[] = [
                'title' => $pageTitle,
                'link' => $pageLink
            ];
            $page = $page->ParentID ? $page->Parent() : false;
        }

        if ($includeHome && $startingPage->URLSegment != 'home') {
            $breadCrumbs[] = [
                'title' => $homeTitle,
                'link' => Director::absoluteBaseURL()
            ];
        }

        $generatedBreadCrumbs = self::setBreadCrumbs(array_reverse($breadCrumbs));
        return $generatedBreadCrumbs;
    }

    public static function setBreadCrumbs($breadCrumbs)
    {
        $structuredBreadCrumbs = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        $count = 1;
        foreach ($breadCrumbs as $breadCrumbItem) {
            $structuredBreadCrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $count,
                'item' => [
                    '@id' => $breadCrumbItem['link'],
                    'name' => $breadCrumbItem['title']
                ]
            ];
            $count++;
        }

        $siteConfig = SiteConfig::current_site_config();

        $siteTitle = $siteConfig->Title;
        $siteTagline = $siteConfig->Tagline;

        $breadCrumbsName = $siteTitle;
        $breadCrumbsDescription = $siteTagline;

        $config = Config::inst();

        if (!isset($config)) {
            throw new Exception("The configuration instance is invalid or null");
        }

        $breadCrumbsName = Config::inst()->get(JsonLDStructuredDataExtension::class, 'breadCrumbs_list_name');
        $breadCrumbsDescription = Config::inst()->get(JsonLDStructuredDataExtension::class, 'breadCrumbs_list_description');

        $structuredBreadCrumbs["name"] = $breadCrumbsName;
        $structuredBreadCrumbs["description"] = $breadCrumbsDescription;

        return $structuredBreadCrumbs;
    }

    public function InjectedStructuredData(array &$structuredDataContainer)
    {
        if (!isset($structuredDataContainer)) {
            throw new Exception("The structured data container is invalid or null");
        }

        $pageOrController = Director::get_current_page();

        if ($pageOrController) {
            $structuredDataContainer[] = JsonLDStructuredDataExtension::generateBreadCrumbs($pageOrController);
            $pageOrController->extend('onInjectStructuredData', $structuredDataContainer);
        }

        for ($i = 0; $i < count($structuredDataContainer); $i++) {
            $structuredDataContainer[$i]["@context"] = JsonLDStructuredDataExtension::SCHEMA_URL;
        }

        $jsonSerializationFlags = JSON_UNESCAPED_SLASHES;
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
