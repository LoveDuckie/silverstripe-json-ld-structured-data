# silverstripe-json-ld-structured-data

A SilverStripe module for conveniently injecting [JSON-LD](https://json-ld.org/) metadata into the header of each rendered page in SilverStripe.

For more information on "[JSON-LD structured data](https://developers.google.com/search/docs/advanced/structured-data/intro-structured-data)", please refer to the Google Developer pages.

[Read more information here about the motivations behind this SilverStripe module.](https://theloveduckie.codes/blog/silverstripe-and-json-ld-structured-data)

## Installation

```shell
composer require silverstripe-module/skeleton 4.x-dev
```

## Requirements

* SilverStripe ^4.0
* [Yarn](https://yarnpkg.com/lang/en/), [NodeJS](https://nodejs.org/en/) (6.x) and [npm](https://npmjs.com) (for building
  frontend assets)

## Maintainers
 * LoveDuckie <loveduckie@gmail.com>

## Documentation
 * [Documentation readme](docs/en/readme.md)
    
## Configuration
    
The extensions in this module will automatically inject itself into the SiteTree type. Refer to the adopted configuration below.

```yaml
---
Name: silverstripe-json-ld-structured-data
---
SilverStripe\CMS\Model\SiteTree:
  extensions:
    - LoveDuckie\SilverStripe\Extensions\JsonLDStructuredDataExtension
```

**Note:** When you have completed your module, submit it to Packagist or add it as a VCS repository to your
project's composer.json, pointing to the private repository URL.

## License
See [License](license.md)
 
## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over 
existing issues to ensure yours is unique. 
 
If the issue does look like a new bug:
 
 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots 
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, 
 Operating System, any installed SilverStripe modules.
 
Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.
 
## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
