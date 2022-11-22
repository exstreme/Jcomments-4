# Package builder

Utility script to build component, module(s), plugin(s) from source folder.

### Howto

Run `php build.php` to build component and all modules and plugins.

Command line options:

* `--com` - build only component package(icluded all plugins listed in package xml).
* `--mod all` - build all modules.
* - `--mod module_name` or `--mod module_name1,module_name2` - build `module_name` module(s).
* `--plg all` - build all plugins.
* - `--plg plugin_name` or `--plg plugin_name1,plugin_name2` - build `plugin_name` plugin(s).

E.g. `php build.php --mod mod_jcomments_latest,mod_jcomments_most_commented`

Package and sha file with hashes will be placed at the root of the repo. Modules and plugins will be placed under the `build` folder.

After building modules and/or plugins(not included within component package) README.md file under the `build` folder will be updated to match extensions versions in this file and extensions versions from xml.

**NOTE!**

`module_name` is a folder name under `modules`.

`plugin_name` is a resulting zip-filename w/o version and extension. E.g. `plg_jcomments_avatar` or `plug_cbjcomments`.
