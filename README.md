<h2>Laybuy module</h2>

## Overview

Module Laybuy\Laybuy implements integration with the Laybuy payment system.

## Installation
    
### Install by uploading files:

#### Download the module as "zip" archive

1. Download the latest release

2. Extract the archive to app/code/Laybuy/Laybuy	

3. Enable the module:

    ```bash
    php bin/magento module:enable Laybuy_Laybuy
    php bin/magento setup:upgrade
    php bin/magento setup:static-content:deploy
    ```

## Requirements

Works with Magento >=2.1

## Dependencies

You can find the list of modules that have dependencies on Laybuy_Laybuy module, in the `require` section of the `composer.json` file located in the same directory as this `README.md` file.

## Extension Points

The Laybuy_Laybuy module does not provide any specific extension points. You can extend it using the Magento extension mechanism.

For more information about Magento extension mechanism, see [Magento plug-ins](http://devdocs.magento.com/guides/v2.0/extension-dev-guide/plugins.html) and [Magento dependency injection](http://devdocs.magento.com/guides/v2.0/extension-dev-guide/depend-inj.html).

## Additional information

For more Magento 2 developer documentation, see [Magento 2 Developer Documentation](http://devdocs.magento.com). Also, there you can track [backward incompatible changes made in a Magento EE mainline after the Magento 2.0 release](http://devdocs.magento.com/guides/v2.0/release-notes/changes/ee_changes.html).
