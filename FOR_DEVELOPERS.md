# Developer's guide

- [Introduction](#introduction)
- [JComments plugin creation](#jcomments-plugin-creation)
- [How to display the comments](#how-to-display-the-comments)
- [How to display the comments quantity](#how-to-display-the-comments-quantity)
- [How to display the last comment of the object](#how-to-display-the-last-comment-of-the-object)
- [How to delete all comments of the object](#how-to-delete-all-comments-of-the-object)
- [How to delete all comments of the given component](#how-to-delete-all-comments-of-the-given-component)

### Introduction

JComments — component that can be easily integrated with any 3rd party extensions without any efforts. You must just add several code lines to your component and write simple plugin for JComments.

The component utilizes plugins to obtain the information about commenting object (the object name and direct link). Plugins are necessary as the component has no information about commenting objects and data structure. That is why such tasks as object link creation and obtaining of its name are assigned to the extension developer.

### JComments plugin creation

The JComments plugin is a common php file that contains successor class of the base class for all plugins of JCommentsPlugin. This plugin contains two methods which are defined as:

* **getObjectTitle($id)** — This method should return title for commenting object by object's ID
* **getObjectLink($id)** — This method should return link to commenting object by object's ID.
* **getObjectOwner($id)** — This method should return ID of commenting object's owner (author).

The name of the exact class must contain two parts, the first with prefix jc_ and the second with component name for which the plugin was written. For example, if the plugin was written for the component com_mycomp the class must have the name: jc_com_mycomp.

The name of the plugin file must contain also two parts: the first one with the component name and the second part of the file name is fixed ??? .plugin.php. For example, if the plugin was written for component com_mycomp, the file name of plugin must be: `com_mycomp.plugin.php`.

After the plugin was created it must be placed in JComments plugin directory. It is planned to write the plugin installer, but in current version only manual installation is possible, i.e. it is necessary to copy the plugin file to folder /components/com_jcomments/plugins/ by FTP or some other way.

Very simple plugin (other examples you can find in plugins directory):

```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_mycomp extends JCommentsPlugin
{
	public function getObjectTitle($id)
	{
		// Data load from database by given id
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('title'))
			->from($db->quoteName('#__mycomp'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function getObjectLink($id)
	{
		// Itemid meaning of our component
		$itemid = self::getItemid('com_mycomp', 'index.php?option=com_mycomp');

		// url link creation for given object by id
		$link = Route::_('index.php?option=com_mycomp&task=view&id=' . $id . '&Itemid=' . $itemid);

		return $link;
	}

	public function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName(array('created_by', 'id')))
			->from($db->quoteName('#__mycomp'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}
}
```

### How to display the comments

You have to include the main JComments file to display the comments somewhere and call the static method `JComments::show()`. This method uses three parameters: object's ID, component name and object's title.

```php
$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

if (is_file($comments))
{
    require_once $comments;

	echo JComments::show($id, 'com_mycomp', $title);
}
```

Where **$id** - is commented object ID, **com_mycomp** - component name and **$title** - is the title of the current object.

### How to display the comments quantity

If you would like to display somewhere the quantity of comments for any object (for example, to display the quantity on an object's intro page), you must include the main JComments file and call static method `JComments::getCommentsCount()` through the function include/require. This method uses two parameters: object's ID and component name.

```php
$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

if (is_file($comments))
{
    require_once $comments;

	$count = JComments::getCommentsCount($id, 'com_mycomp');

	echo $count ? ('Comments(' . $count . ')') : 'Add comment';
}
```

where **$id** - is the commented object ID and **com_mycomp** - component name. This code displays the 'Comments (5)' if you have 5 comments of given object and 'Add comments' if there are no comments.

### How to display the last comment of the object

If you would like to display the last comment of any object, you must include the main JComments file through include/require and call static method `JComments::getLastComment()`. This method uses two parameters: object's ID and component name.

```php
$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

if (is_file($comments))
{
    require_once $comments;

	$comment = JComments::getLastComment($id, 'com_mycomp');

	echo 'User "' . $comment->name . '" wrote "' . $comment->comment . '" (' . $comment->date . ')';
}
```

where the **$id** - is the commented object ID and **com_mycomp** - component name. This code displays 'User "Administrator" wrote "This is sample comment" at "2007-02-07 16:52:53".

### How to delete all comments of the object

If you would like to delete all comments of any object, you must include the main JComments file through include/require and call static method `JComments::deleteComments()`. This method uses two parameters: object's ID and component name.

```php
$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

if (is_file($comments))
{
    require_once $comments;

	JComments::deleteComments($id, 'com_mycomp');
}
```

where **$id** - is the commented object ID and **com_mycomp** - component name. After the method is called all comments of a given object will be deleted.

### How to delete all comments of the given component

If you would like to delete all comments of any object, you have to include the main JComments file through include/require and call static method `JComments::deleteAllComments()`. This method uses only one parameter: component name.

```php
$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

if (is_file($comments))
{
    require_once $comments;

	JComments::deleteAllComments('com_mycomp');
}
```

After the method is called all comments of a given component will be deleted.
