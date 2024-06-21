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

    /**
     * @return string
     * @throws Exception
     */
    public function PageStructuredData(): string
    {
        $structuredDataContainer = [];
        return $this->InjectedStructuredData($structuredDataContainer);
    }

    /**
     * @param $controller
     * @return array|null
     * @throws Exception
     */
    public static function generateBreadCrumbsFromController($controller): ?array
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

    /**
     * @param $pageOrController
     * @return array|null
     * @throws Exception
     */
    public static function generateBreadCrumbs($pageOrController): ?array
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

    /**
     * @param $page
     * @param $includeHome
     * @param $homeTitle
     * @return array
     * @throws Exception
     */
    public static function generateBreadCrumbsFromSiteTree($page, $includeHome = true, $homeTitle = 'Home')
    {
        $breadCrumbs = [];
        $startingPage = $page;

        if ($startingPage->hasMethod('generateBreadCrumbs')) {
            $breadCrumbs = $startingPage->generateBreadCrumbs($breadCrumbs);
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

    /**
     * @param $breadCrumbs
     * @return array
     * @throws Exception
     */
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

        $breadcrumbsList = self::getConfigProperty('breadcrumbs_list');

        $breadCrumbsName = $breadcrumbsList['default_name'];
        $breadCrumbsDescription = $breadcrumbsList['default_description'];

        $useSiteConfigTitleAsName = $breadcrumbsList['use_siteconfig_title_as_name'];
        $useSiteConfigTaglineAsDescription = $breadcrumbsList['use_siteconfig_tagline_as_description'];

        if (empty($breadCrumbsName) || $useSiteConfigTitleAsName) {
            $breadCrumbsName = $siteTitle;
        }
        if (empty($breadCrumbsDescription) || $useSiteConfigTaglineAsDescription) {
            $breadCrumbsDescription = $siteConfig->Tagline;
        }

        $structuredBreadCrumbs["name"] = $breadCrumbsName;
        $structuredBreadCrumbs["description"] = $breadCrumbsDescription;

        return $structuredBreadCrumbs;
    }

    private static function getConfigProperty(string $propertyName)
    {
        if (empty($propertyName)) {
            throw new Exception("The name of the property was not defined. Unable to continue.");
        }

        return Config::inst()->get(JsonLDStructuredDataExtension::class, $propertyName);
    }

    public function InjectedStructuredData(array &$structuredDataContainer)
    {
        if (!isset($structuredDataContainer)) {
            throw new Exception("The structured data container is invalid or null");
        }
        $siteConfig = SiteConfig::current_site_config();

        $siteTitle = $siteConfig->Title;
        $siteTagline = $siteConfig->Tagline;

        $tagsConfig = self::getConfigProperty('tags');
        if ($tagsConfig['website']['enable']) {
            $structuredDataContainer[] = [
                "@type" => "WebSite",
                "about" => [],
                "url" => Director::absoluteBaseURL(),
                "name" => $siteTitle,
                "description" => $siteTagline
            ];
        }

        $pageOrController = Director::get_current_page();

        if ($pageOrController) {
            if ($tagsConfig['breadcrumbs']['enable']) {
                $structuredDataContainer[] = JsonLDStructuredDataExtension::generateBreadCrumbs($pageOrController);
            }
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
