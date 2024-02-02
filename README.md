# (Dev) JComments 4.1

Adapting the component to work with Joomla 4.

## TODO

- [x] Make backend to work with new Joomla 4 MVC.
- [ ] Make frontend to work with new Joomla 4 MVC.


## Quick Start

There are currently no releases to install. Use build.php script to make package.

## Migration from Jcomments 3
- make backup
- uninstall Jcomments 3 (your comments will be saved in the database)
- install Jcomments 4
- run database repair if need from `administrator/index.php?option=com_installer&view=database`
- Go to `administrator/index.php?option=com_config&view=component&component=com_jcomments` and set up access rules again

## Modules

Available modules can be downloaded <a href="https://github.com/exstreme/Jcomments-4/tree/master/build/modules" target="_blank">here</a>.

## Plugins

Available plugins can be downloaded <a href="https://github.com/exstreme/Jcomments-4/tree/master/build/plugins" target="_blank">here</a>.

## FAQ

* Does this component support hCaptcha plugin?
* Yes! Download plugin from https://extensions.joomla.org/extension/hcaptcha/, install it, configure, activate, and select in component settings.


* How do I get RSS for...
* See https://github.com/exstreme/Jcomments-4/issues/130#issuecomment-1409095204

## Events

- onTableBeforePin
- onTableAfterPin
- onJCommentsUserBeforeBan
- onJCommentsUserAfterBan
- onJCommentsCommentsPrepare
- onPrepareAvatars
- onJcommentsCleanCache
- onJCommentsCommentBeforePrepare
- onJCommentsCommentAfterPrepare
- onMailBeforeNotificationPush
- onMailBeforeSend
- onMailAfterSend
- onJCommentsCommentBeforeVote
- onJCommentsCommentAfterVote
- onJCommentsCommentBeforeReport
- onJCommentsCommentAfterReport
- onJCommentsFormBeforeDisplay
- onJCommentsFormAfterDisplay
- onJCommentsFormPrepend
- onJCommentsFormAppend
- All other standard Joomla events from MVC where it is applicable.
