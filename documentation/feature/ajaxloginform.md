Ajax Login Form
==================

Description
--
When enabled, you will get access to some extra shortcodes and functions which should kickstart
the development of an AJAX based login process for Wordpress websites.

The feature does not only cover the login process, but also has code to handle forgot password
and reset password forms.

**Basic Setup**

The premise of this feature is that you basically want to have a login form on every page and do not
want to use the standard wp-login.php for handling the login. 

In your base template(s) you will then call the function `woppe_ajax_loginform()`. This will generate a
HTML login form which should probably be enough to get you going. You can however change how this looks
by creating a completely custom HTML. Be aware though that some things will have to remain the same
in order for javascript to work!


**Good to know**

* This feature requires the rights to write a javascript file somewhere in your document root. It will try to
  do this on the following locations:
  1. \<theme\>/dist/scripts (as per Sage setup)
  2. \<theme\>/js
  3. \<theme\>
  4. dirname(ABSPATH) (assuming you use wordpress in a subdirectory setup)
  5. basedir
  

Usage
---

`add_theme_support('woppe-ajax-login-form');`

**Parameters**

None


Filters & Hooks
---
This feature utilises a lot of filters to make it easier for you to tweak and bend this feature to your own needs.
