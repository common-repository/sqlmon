<?php

	$sqlmon_path = (defined('WP_PLUGIN_DIR')) ? (WP_PLUGIN_DIR . '/sqlmon/') : (ABSPATH . 'wp-content/plugins/sqlmon/');

	if (
		   false === (include_once($sqlmon_path . 'lib/class.Logger.php')) ||
		   false === (include_once($sqlmon_path . 'lib/class.LogEntry.php'))
	   )
	{
	/*
		We failed to include one of the required files, this usually means that the plugin was deleted and
		its deactivation failed. Pass the control back to WordPress and give up loading.
	*/
		unset($sqlmon_path);
		require_once(ABSPATH . 'wp-includes/wp-db.php');
		return;
	}

	unset($sqlmon_path);

	/**
	 * @global DbProfile $wpdb
	 */
	$wpdb = false;

	/*
		We have to have the definition of wpdb class if we want to subclass it.
		Here comes $wpdb = false.
		Since $wpdb variable is set, WordPress won't try to establish a connection
		to the database using wpdb class.
	*/
	require_once(ABSPATH . 'wp-includes/wp-db.php');

	class DbProfile extends wpdb
	{
		/**
		 * @var Logger
		 */
		private $logger;

		/**
		 * @var string
		 */
		public $dbname;

		/**
		 * @var bool
		 */
		public $enabled = true;

		public function __construct($user, $password, $dbname, $host)
		{
			$this->logger = Logger::instance();
			$this->dbname = $dbname;

			$entry = new LogEntry();
			$entry->query  = "CONNECT {$user}:password@{$host}/{$dbname}";
			$entry->time   = Logger::getTime();
			$entry->dbname = $dbname;

			parent::__construct($user, $password, $dbname, $host);

			$entry->time      = Logger::getTime() - $entry->time;
			$entry->backtrace = DbProfile::_traceBack();
			$entry->count     = 0;
			$entry->errcode   = mysql_errno($this->dbh);
			$entry->error     = mysql_error($this->dbh);

			$this->logger->addToLog($entry);
		}

		public function query($query)
		{
			if (!$this->enabled) {
				return parent::query($query);
			}

			$entry = new LogEntry();
			$entry->query  = $query;
			$entry->time   = Logger::getTime();
			$entry->dbname = $this->dbname;

			if (defined('SQLMON_SHOW_EXPLAIN') && SQLMON_SHOW_EXPLAIN) {
				$q = $query;
				//DELETE [table] FROM tables ... => SELECT * FROM tables
				$q = preg_replace('/^(\\s*DELETE\\s.*?FROM)/ism', "SELECT * FROM\n", $q);
				//UPDATE table SET data [WHERE...] => SELECT * FROM table [WHERE...]
				$q = preg_replace('/^(\\s*UPDATE\\s+)/ism', "SELECT * FROM\n", $q);
				$q = preg_replace('/(\\s+SET\\s+.*?WHERE)/ism', "\nWHERE\n", $q);
				$q = preg_replace('/(\\s+SET\\s+.*?)/ism', "", $q);
				$matches = array();
				//Trying to extract SELECT from INSERT/REPLACE INTO ... AS or CREATE TABLE ... AS
				if (preg_match('/(SELECT\\s.*?FROM\\s.*$)/ism', $q, $matches)) {
					//Got SELECT, now do EXPLAIN SELECT
					$res = @mysql_unbuffered_query("EXPLAIN EXTENDED\n" . $matches[1], $this->dbh);
					if (false !== $res) {
						$x = array();
						while (false !== ($row = mysql_fetch_assoc($res))) {
							$x[] = $row;
						}

						mysql_free_result($res);
						$entry->explain = $x;

						if (defined('SQLMON_SHOW_EXTENDED') && SQLMON_SHOW_EXTENDED) {
							$res = @mysql_unbuffered_query('SHOW WARNINGS', $this->dbh);
							if (false !== $res) {
								$x = array();
								while (false !== ($row = mysql_fetch_assoc($res))) {
									$x[] = $row['Message'];
								}

								mysql_free_result($res);
								$entry->rewritten = $x;
							}
						}
					}
				}
			}

			$res = parent::query($query);

			$entry->time    = Logger::getTime() - $entry->time;
			$entry->count   = mysql_affected_rows($this->dbh);
			$entry->errcode = mysql_errno($this->dbh);
			$entry->error   = mysql_error($this->dbh);

			if (defined('SQLMON_SHOW_BACKTRACE') && SQLMON_SHOW_BACKTRACE) {
				$entry->backtrace = DbProfile::_traceBack();
			}

			$this->logger->addToLog($entry);

			return $res;
		}

		/**
		 * @desc Traces back the call stack
		 * @internal
		 * @return string
		 */
		static private function _traceBack()
		{
			$backtrace = debug_backtrace();
			$len       = count($backtrace);

			$where = '';

			//The very first (zeroth) entry can be ignored - it is a call to _traceBack()
			for ($i=1; $i<$len; ++$i) {
				if (!isset($backtrace[$i]['file'])) {
					$backtrace[$i]['file'] = '(undefined)';
				}

				if (!isset($backtrace[$i]['line'])) {
					$backtrace[$i]['line'] = '(undefined)';
				}

				if (!isset($backtrace[$i]['class'])) {
					$backtrace[$i]['class'] = '';
				}

				if (!isset($backtrace[$i]['function'])) {
					$backtrace[$i]['function'] = '';
				}

				if (defined('ABSPATH') && !strncmp(ABSPATH, $backtrace[$i]['file'], strlen(ABSPATH))) {
					$backtrace[$i]['file'] = substr($backtrace[$i]['file'], strlen(ABSPATH));
				}

				$where .= $backtrace[$i]['file'] . ', ' . $backtrace[$i]['line'];
				if ($backtrace[$i]['class'] || $backtrace[$i]['function']) {
					$where .= ' (';

					if ($backtrace[$i]['class']) {
						$where .= $backtrace[$i]['class'] . '::';
					}

					$where .= $backtrace[$i]['function'] . ')';
				}

				$where .= "\n";
			}

			$where = substr($where, 0, -1);

			if (empty($where)) {
				$where = 'undefined location';
			}

			return $where;
		}
	}

	$wpdb = new DbProfile(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
?>