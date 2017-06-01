# Set of Wordpress extensions to accelerate theme/site development

This library contains some tools and classes that may help when developing 
Wordpress sites based on roots(derived) themes.

The library has several main components:

 - Theme Support

### Theme support

Starting with 0.3.0 the use of the wordpress function `add_theme_support()` is also 
supported in this extension. Currently there are a couple of supports you can add:

* **Autoload Custom (woppe-autoload-custom)**:
  Autoloads all PHP files in lib/custom (or other folder if specified)
  This feature will become deprecated in favor of autoload files.
* **Autoload Files (woppe-autoload-files)**
  Same as autoload custom, but loads them from src/files instead of lib/custom
* **Disabled Comments (woppe-disabled-comments)**
  Disable comment support on all post types (except the ones given as parameters)
* **Enable restricted page (woppe-enable-restricted-page)**
  Instead of giving a 404 when someone tries to load a page that is restricted,
  redirect the user to a nice page, which shows some restricted message.
* **Extended Search (woppe-extended-search)**
  A feature which will allow you to use the custom search solution which can also
  search through ACF fields
* **Google Analytics (woppe-google-analytics)**:
  Adds a google analytics tracker to your pages (will be placed in the head).
* **Hide post type (woppe-hide-post-type)**
  Hides post types from the outside
* **HTML Mail (woppe-html-mail)**:
  Adds HTML Mail support to wordpress
* **Practical Settings (woppe-practical-settings)**
  A combination from some other fearures like disable comments and varnish) and disables
  the wp-json API.
* **Varnish (woppe-varnish)**: 
  Adds support for varnish and automatic varnish flush.
* **  

Please see the feature documentation for usage information

