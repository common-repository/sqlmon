=== SQL Monitor ===
Contributors: vladimir_kolesnikov
Donate link: http://blog.sjinks.pro/feedback/
Tags: sql, performance, debug, speed, slow, tuning, database
Requires at least: 2.6
Tested up to: 3.2
Stable tag: 0.6.1.1

The plugin analyzes every query to the database. Warning: For PHP 5 only.

== Description ==

Almost every developer knowns that the poor performance in many cases is due to poorly designed database or SQL queries
(for example, when the table does not have indices that the query can use or when the query uses unnecessary
performance killers such as ORDER BY/GROUP BY).

WordPress can provide only the list of the queries and the developer has to manually run every query and analyze it.
Very boring, isn't it? SQLMon tries to help the developers and analyzes the query itself, reporting everything that needs attention.

Every query passed to WordPress is analyzed (using EXPLAIN) and the results are shown in the theme's footer or Admin panel's footer
(they are visible to site administrators only — this allows to use SQLMon even on live sites).

One of the key features of SQLMon is that it can run EXPLAIN not only on SELECT queries but also on UPDATE/DELETE and INSERT/REPLACE INTO … AS or
CREATE TABLE … AS.

The plugin is perfect for WordPress developers, plugin and theme authors and site administrators who are trying to find out why the blog is too slow.

**IMPORTANT:** the project has moved to [Launchpad](https://launchpad.net/wp-plugin-sqlmon), please use it to report all bugs, suggest improvements,
request features and get the latest development version.

== Installation ==

1. Upload `sqlmon` folder to the `/wp-content/plugins/` directory.
1. Please make sure that `wp-content` directory is writable by your web server (when activated, SQLMon will copy `db.php` to `wp-content`).
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. If everything is fine, you will see `db.php` in your `wp-content` directory.

== Frequently Asked Questions ==

None yet. Be the first to ask.

== Screenshots ==

1. This is how the results might look.

== Warning ==

This plugins requires that PHP 5 be installed.
No support for ancient PHP 4, sorry.

== Incompatibilities ==

SQLMon is incompatible with plugins that install their own `db.php` into `wp-content` directory (DB Cache, DB Cache Reloaded, probably W3 Total Cache).

== Removal ==

If you want to remove the plugin completely, please deactivate it first and make sure that `wp-content/db.php` is deleted (if not, please delete it yourself).

== Recommended Reading ==

[Optimizing Queries with EXPLAIN](http://dev.mysql.com/doc/refman/5.1/en/using-explain.html)

== Bug Reports ==

Please use [Launchpad](https://bugs.launchpad.net/wp-plugin-sqlmon) to report any bugs.

== Changelog ==
= 0.6.1.1 =
* Applied [jeremyclarke's](http://wordpress.org/support/profile/jeremyclarke) color scheme

= 0.6.1 =
* Bugs fixed: LP #628621
* Display how the optimizer qualifies table and column names in the SELECT statement, what the SELECT looks like after the application of rewriting and optimization rules, and other notes about the optimization process
* [More details](https://launchpad.net/wp-plugin-sqlmon/+milestone/0.6.1)

= 0.6 =
* WordPress 3.1-alpha compatibility (LP #627209)

= 0.5.9 =
* CONNECT times are now also shown in the logs

= 0.5.8.1 =
* Removed forgotten unserialize()

= 0.5.8 =
* Code optimization. Got rid of EXPLAIN serialization.

= 0.5.7 =
* Added a check if wp-content/db.php exists before trying to remove it on deactivation (no errors are thrown if the file does not exist)
* Minor cleanups
* Minified CSS

= 0.5.6 =
* Major code refactoring

= 0.5.4 =
* Code cleanup
* Throws a warning when wp-content/db.php cannot be removed on plugin deactivation
* Does not cause the white screen of death when the plugin is removed and wp-content/db.php is not.

= 0.5.1 =
* Code cleanup
* Support for multiple databases

= 0.5 =
* First public avaliable version
