<?php
/*
Plugin Name: SQL Monitor
Description: SQL Query Debugger
Version: 0.6.1.1
Plugin URI: http://blog.sjinks.pro/wordpress-plugins/sqlmon/
Author: Vladimir Kolesnikov
Author URI: http://blog.sjinks.pro/
License: BSD
*/
	require_once(WP_PLUGIN_DIR . '/sqlmon/lib/class.Logger.php');
	require_once(WP_PLUGIN_DIR . '/sqlmon/lib/class.LogEntry.php');

	define('SQLMON_SHOW_BACKTRACE', true);
	define('SQLMON_SHOW_EXPLAIN',   true);
	define('SQLMON_SHOW_EXTENDED',  false);

	class SqlMonitor
	{
		/**
		 * @var Logger
		 */
		protected $logger;

		public static function instance()
		{
			static $self = false;
			if (!$self) {
				$self = new SqlMonitor();
			}

			return $self;
		}

		protected function __construct()
		{
			global $wpdb;

			if ($wpdb instanceof DbProfile) {
				add_action('init', array($this, 'init'));
			}

			$this->logger = Logger::instance();

			add_action('activate_sqlmon/sqlmon.php',   array($this, 'activate'));
			add_action('deactivate_sqlmon/sqlmon.php', array($this, 'deactivate'));
		}

		public function init()
		{
			global $wpdb;

			$allow = apply_filters('enable_sqlmon', current_user_can('administrator'));
			if ($allow) {
				wp_enqueue_style('sqlmon-css', plugins_url('sqlmon/sqldebug.css'), array(), '', 'all');

				add_action('wp_footer',    array($this, 'footer'), 999999);
				add_action('admin_footer', array($this, 'footer'), 999999);
			}
			else {
				$wpdb->enabled = false;
			}
		}

		public function footer()
		{
			$this->logger->dump(false);
		}

		public function activate()
		{
			@copy(dirname(__FILE__) . '/db.php', WP_CONTENT_DIR . '/db.php');
		}

		public function deactivate()
		{
			if (file_exists(WP_CONTENT_DIR . '/db.php')) {
				if (!@unlink(WP_CONTENT_DIR . '/db.php')) {
					trigger_error("Failed to delete " . WP_CONTENT_DIR . "/db.php. Please remove this file manually.", E_USER_WARNING);
				}
			}
		}
	}

	/**
	 * @desc Dumps the list of the queries
	 * @param bool $clear Clear the query log
	 * @param bool $force_css Forcefully include the CSS file
	 */
	function sqlmon_dump_queries($clear = false, $force_css = false)
	{
		if ($force_css) {
			$url = esc_attr(plugins_url('sqlmon/sqldebug.css'));
			echo '<link rel="stylesheet" type="text/css" href="', $url, '"/>';
		}

		$logger = Logger::instance();
		$logger->dump();

		if ($clear) {
			$logger->clearLog();
		}
	}

	SqlMonitor::instance();
?>