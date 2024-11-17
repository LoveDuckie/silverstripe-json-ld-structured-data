# Documentation

Find below some information about the configuration file you can use for customising the behaviour of this extension.

## Configuration

```yaml
---
Name: app-your-config-name-goes-here
After: silverstripe-json-ld-structured-data
---
LoveDuckie\SilverStripe\JsonLDStructuredData\Extensions\JsonLDStructuredDataExtension:
  breadcrumbs_list:
    default_name: 'The default name for your website.'
    default_description: 'The default description for your website.'
    use_siteconfig_title_as_name: true
    use_siteconfig_tagline_as_description: true
  tags:
    website:
      enable: true
    breadcrumbs:
      enable: true
```
