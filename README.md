# HarperJones' set of Wordpress extensions to accelerate theme/site development

This library contains some tools and classes that may help when developing 
Wordpress sites based on roots(derived) themes.

The library has several main components:

 - Shortcode starter class
 - Theme plugins
 - Theme Support

## Shortcode starter class
The shortcode starter class can be used to kickstart the development of a new
Wordpress shortcode. When implementing a new shortcode create a new class that
extends the `HarperJones\Wordpress\Shortcode\AbstractShortcode` class. 

The abstract class will act as the base routing handler for your shortcode. All
you have to do is call the `register` method, and implement the controller 
functions.

for routing it uses the `_action` url parameter. Based on that parameter or the 
value `index`, if the parameter does not exist, it will check the following
methods in your class:

- post_<action>
- get_<action>
- action_<action>
- action_index

The methods should return a string or an instance of `HarperJones\Wordpress\Theme\View`.
If no output was given, the controller will continue onto the next method. This 
allows you to daisy-chain multiple methods to seperate logic (POST handling logic
in the post_ method, normal displaying in the action_ method).

A simple sample Shortcode class would look like this:

```
#!php

use Symfony\\Component\\HttpFoundation\\Request;

class HelloWorldShortcode extends \\HarperJones\\Wordpress\\Shortcode\\AbstractShortcode
{
	public function __construct()
	{
		$this->register('helloworld');
	}
	
	public function action_index(Request $request) 
	{
		return 'hello ' . $request->request->get('who','world') . '!';
	}
}
```

## Theme Plugins

Theme plugins were created to make it easier to create small code snippets which
you could recycle in multiple projects. We created it for two reasons:

- Adding a lot of plugins in Wordpress is generally considered a "bad thing"
- Plugin loading is relatively slow
- We prefer to have all our js/css compiled and compressed into just a few files per theme

To add support for theme plugins, add the following line somewhere in your theme 
functions/library:

```
#!php

$_themePlugins = new \HarperJones\Wordpress\Theme\Plugin\Manager();
```

By default, the manager will expect plugins to be stored in the `plugins` directory
of the theme. You can modify this by adding a new path (relative to the theme). Be 
aware that plugins are loaded on the `after_theme_setup` action! 

Furthermore you can have an paths.json file in your theme, which will tell plugins
where to install their "installable" files (js, css etc). This file should contain
a json string and defaults to this:

```
#!json
{
	"vendor": "/assets/js/plugins",
	"custom": "/assets/js/",
	"css": "/assets/css",
	"less": "/assets/less"
}
```

### Theme support

Starting with 0.3.0 the use of the wordpress function `add_theme_support()` is also 
supported in this extension. Currently there are a couple of supports you can add:

* **Autoload Custom (harperjones-autoload-custom)**:
  Autoloads all PHP files in lib/custom (or other folder if specified)
* **Google Analytics (harperjones-google-analytics)**:
  Adds a google analytics tracker to your pages (will be placed in the head).
* **HTML Mail (harperjones-html-mail)**:
  Adds HTML Mail support to wordpress
* **Varnish (harperjones-varnish)**: 
  Adds support for varnish and automatic varnish flush.

Please see the feature documentation for usage information

### Package contents

All packages should contain a file named `plugin.json`. This file should
contain some basic information about the plugin. The following entries are possible:

**name (required)**
The name variable should contain the official name of the plugin. This name will also
be used as "id", so it is best to keep names in the subset `[a-zA-Z0-9_]`.

**namespace**
The namespace of the plugin. When used, it will expect the plugin to have a `src` 
directory, which can be used as a psr4 root for the given namespace. Setting this
option will also imply the plugin is named <namespace>\<name>. If this option is
not set the `file` option _must_ be set.
When set, the path will be added as PSR4 path in the autoloader.

**file**
The file option allows you to point to a file in the plugin project that should be 
included to "start" the plugin. 

**install**
The install option allows you to point to files which should be copied to the theme
directories upon install/update.

### Minimum Plugin

A minimum plugin should have two files: `plugin.json` and your plugin code, say 
`plugin.php`. In this case the plugin.json will look like this:
 
```
#!json
{
  "name": "HJ_Miscellaneous",
  "file": "plugin.php"
} 
``` 

The file `plugin.php` can then be used to hook certain events, or create your
customization.

### Using the Plugin base class

Provided with this package is a Plugin base class you can use. This class has some
options to quickly "hook" to your Wordpress instance. Please see the documentation
of that class to figure out how it works.

Some bullet points:

**Add action hooks**
You can add action hooks by overriding the `$on` attribute of the class. The `$on` 
attribute should be an array where the keys are Wordpress [actions](http://codex.wordpress.org/Plugin_API/Action_Reference),
and the value should be a callable method/function or string. If a string was given
that is not an existing function, the plugin will assume you mean to call a method 
within that class. So `someAction` becomes a shorthand for `array($this,'someAction')`.

**Add filter hooks**
Same as with actions, you can also quickly hook filters by overriding the `$filter` attribute.
The functioning is pretty much the same. If you want to have more control on when the
filter is called (priority), you should hook an `init` action, and use the `apply_filters`
function yourself

**Influencing a plugin**
Every plugin that extends the Plugin class has a couple of filters you can hook into,
to manipulate the class. All filters are start with `themeplugin_<lowercase name>_`.
Available options are:

* **config**:
This allows you to add configuration options. These config options will be made available
through the `$config` attribute.
* **on**:
 
