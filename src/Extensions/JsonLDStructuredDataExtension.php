<?php

namespace LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class JsonLDStructuredDataExtension extends DataExtension
{
    use Configurable;

    private const SCHEMA_URL = "https://schema.org/";

    /**
     * @var array
     */
    private static array $default_config = [
        'breadcrumbs_list' => [
            'default_name' => 'Default Site',
            'default_description' => 'Default Description',
            'use_siteconfig_title_as_name' => true,
            'use_siteconfig_tagline_as_description' => true,
        ],
        'tags' => [
            'website' => ['enable' => true],
            'breadcrumbs' => ['enable' => true],
        ],
    ];

    private static $casting = [
        'PageStructuredData' => 'HTMLFragment'
    ];

    /**
     * @return string
     * @throws NotFoundExceptionInterface
     */
    public function PageStructuredData(): string
    {
        $structuredData = [];
        $this->addWebSiteData($structuredData);
        $this->addBreadCrumbsData($structuredData);
        $this->addStructuredData($structuredData);

        return $this->serializeStructuredData($structuredData);
    }

    private function addStructuredData(array &$structuredData): void
    {
        $pageOrController = Director::get_current_page();
        $pageOrController?->extend('onInjectStructuredData', $structuredData);
    }

    /**
     * @param array $structuredData
     * @return void
     */
    private function addWebSiteData(array &$structuredData): void
    {
        $tagsConfig = self::getConfigValue('tags');
        if ($tagsConfig['website']['enable']) {
            $siteConfig = SiteConfig::current_site_config();
            $structuredData[] = [
                '@type' => 'WebSite',
                'url' => Director::absoluteBaseURL(),
                'name' => $siteConfig->Title,
                'description' => $siteConfig->Tagline
            ];
        }
    }

    /**
     * @param array $structuredData
     * @return void
     */
    private function addBreadCrumbsData(array &$structuredData): void
    {
        $tagsConfig = self::getConfigValue('tags');
        if ($tagsConfig['breadcrumbs']['enable']) {
            $pageOrController = Director::get_current_page();
            if ($pageOrController) {
                $structuredData[] = self::generateBreadCrumbs($pageOrController);
            }
        }
    }

    /**
     * @param $pageOrController
     * @return array|null
     */
    private static function generateBreadCrumbs($pageOrController): ?array
    {
        if ($pageOrController instanceof ContentController) {
            return self::generateBreadCrumbsFromController($pageOrController);
        } elseif ($pageOrController instanceof SiteTree) {
            return self::generateBreadCrumbsFromSiteTree($pageOrController);
        }
        return null;
    }

    /**
     * @param $controller
     * @return array|null
     */
    private static function generateBreadCrumbsFromController($controller): ?array
    {
        if ($controller->hasMethod('generateBreadCrumbs')) {
            return $controller->generateBreadCrumbs([]);
        }
        return null;
    }

    /**
     * @param $page
     * @param true $includeHome
     * @param string $homeTitle
     * @return array
     */
    private static function generateBreadCrumbsFromSiteTree($page, true $includeHome = true, string $homeTitle = 'Home'): array
    {
        $breadcrumbs = [];

        if ($page->hasMethod('generateBreadCrumbs')) {
            $page->generateBreadCrumbs($breadcrumbs);
        }

        while ($page) {
            $breadcrumbs[] = [
                'title' => $page->Title,
                'link' => $page->AbsoluteLink()
            ];
            $page = $page->ParentID ? $page->Parent() : null;
        }

        if ($includeHome && $homeTitle) {
            $breadcrumbs[] = [
                'title' => $homeTitle,
                'link' => Director::absoluteBaseURL()
            ];
        }

        return self::formatBreadCrumbs(array_reverse($breadcrumbs));
    }

    /**
     * @param array $breadcrumbs
     * @return array
     */
    private static function formatBreadCrumbs(array $breadcrumbs): array
    {
        $structuredData = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $structuredData['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@id' => $breadcrumb['link'],
                    'name' => $breadcrumb['title']
                ]
            ];
        }

        return $structuredData;
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    private static function getConfigValue(string $key, $default = null): mixed
    {
        $config = Config::inst()->get(JsonLDStructuredDataExtension::class, $key);
        return $config ?? self::$default_config[$key] ?? $default;
    }

    /**
     * @param array $structuredData
     * @return string
     * @throws NotFoundExceptionInterface
     */
    private function serializeStructuredData(array $structuredData): string
    {
        foreach ($structuredData as &$item) {
            $item['@context'] = self::SCHEMA_URL;
        }

        $flags = JSON_UNESCAPED_SLASHES | (Director::isDev() || Director::isTest() ? JSON_PRETTY_PRINT : 0);
        $json = json_encode($structuredData, $flags);

        if (!$json) {
            $this->logError('Failed to serialize structured data to JSON.');
            return '';
        }

        return <<<HTML
<script type="application/ld+json">
$json
</script>
HTML;
    }

    /**
     * @param string $message
     * @return void
     * @throws NotFoundExceptionInterface
     */
    private static function logError(string $message)
    {
        Injector::inst()->get(LoggerInterface::class)->error($message);
    }
}
