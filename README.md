# JComments 4.0 (Stable)

![](https://img.shields.io/github/stars/exstreme/Jcomments-4.svg) ![](https://img.shields.io/github/forks/exstreme/Jcomments-4.svg) ![](https://img.shields.io/github/tag/exstreme/Jcomments-4.svg) ![](https://img.shields.io/github/release/exstreme/Jcomments-4.svg) ![](https://img.shields.io/github/issues/exstreme/Jcomments-4.svg)

This branch is stable. Adapting the component to work with Joomla 4 continuing in 4.1 branch.

## Quick Start

Download <a href="https://github.com/exstreme/Jcomments-4/releases/latest" target="_blank">latest version</a> of package

## Requirements

Joomla 4.2+

Joomla 5.0+

## Migration from Jcomments 3
- Make backup
- Uninstall Jcomments 3 (your comments will be saved in the database)
- Install Jcomments 4
- Run database repair if need from `administrator/index.php?option=com_installer&view=database`
- Go to `administrator/index.php?option=com_config&view=component&component=com_jcomments` and set up access rules again

## Modules

Available modules can be downloaded <a href="https://github.com/exstreme/Jcomments-4/tree/master/build/modules" target="_blank">here</a>.

## Plugins

Available plugins can be downloaded <a href="https://github.com/exstreme/Jcomments-4/tree/master/build/plugins" target="_blank">here</a>.

## Integrations

The JComments has integrations with other components. See the full list in the folder https://github.com/exstreme/Jcomments-4/tree/master/component/site/plugins

**NOTE!** For _**FW Gallery**_ and _**VirtueMart**_ integration see `Extra section` at https://github.com/exstreme/Jcomments-4/blob/master/FOR_DEVELOPERS.md

## FAQ

* Does this component support hCaptcha plugin?
* Yes! Download plugin from https://extensions.joomla.org/extension/hcaptcha/, install it, configure, activate, and select in component settings.


* How do I get RSS for...
* See https://github.com/exstreme/Jcomments-4/issues/130#issuecomment-1409095204
