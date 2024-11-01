<?php
	require_once('class.AbstractLogEntry.php');

	/**
	 * @package Database
	 * @desc Log Entry
	 */
	class LogEntry extends AbstractLogEntry
	{
		/**
		 * @desc Query
		 * @var string
		 */
		public $query = null;

		/**
		 * @desc Database on which the query was executed
		 * @var string
		 */
		public $dbname = null;

		/**
		 * @desc Query backtrace (call stack) - to identify the place from where the query was made
		 * @var string
		 */
		public $backtrace = null;

		/**
		 * @desc Error Message from SQL
		 * @var string
		 */
		public $error = null;

		/**
		 * @desc EXPLAINed query
		 * @var string
		 */
		public $explain = null;

		/**
		 * @desc Error Code from SQL
		 * @var int
		 */
		public $errcode = 0;

		/**
		 * @desc Number of rows affected/selected, if applicable
		 * @var int|string '?', if SELECT was unbuffered
		 */
		public $count = 0;

		public function renderHeader()
		{
			echo "<tr><th>#</th><th>Query</th><th>ErrCode</th><th>Results</th><th>Time</th></tr>";
		}

		public function render($row_no)
		{
			$id = substr(uniqid(time(), true), -6);
			$q = esc_attr(trim($this->query));

			$q = '<span class="query">' . nl2br($q) . "<br/>/*{$this->dbname}*/</span>";

			if ($this->errcode) {
				$q .= "<div><i style=\"color: #F00\">" . nl2br(esc_attr($this->error)) . "</i></div>";
			}

			if (count($this->explain)) {
				$q .= LogEntry::resToTable(LogEntry::explainQuery($this->explain));
			}

			if (trim($this->backtrace)) {
				$q .= "<div><b style=\"color: #0000F0\">" . nl2br(esc_attr($this->backtrace)) . "</b></div>";
			}

			$t = round($this->time, 5);
			$c = $this->count;
			if (!$c || '?' == $c) {
				$c = '&mdash;';
			}
			$e = $this->errcode;
			if ($e) {
				$e = '<span style="color: red; font-weight: bold">' . intval($e) . '</span>';
				$q = '<span style="color: red">' . $q . '</span>';
			}

			echo "<tr><td>{$row_no}</td><td>{$q}</td><td>{$e}</td><td>{$c}</td><td>{$t}</td></tr>";
		}

		static protected function explainQuery($entry)
		{
			if (empty($entry)) {
				return array();
			}

			foreach ($entry as $k => $x) {
				$x = array_map('esc_attr', $x);

				$select_type   = &$x['select_type'];
				$type          = &$x['type'];
				$possible_keys = &$x['possible_keys'];
				$key           = &$x['key'];
				$key_len       = &$x['key_len'];
				$ref           = &$x['ref'];
				$rows          = &$x['rows'];
				$extra         = &$x['Extra'];

				switch ($select_type) {
					case 'UNCACHEABLE SUBQUERY': $select_type = "<strong class='red'>{$select_type}</strong>"; break;
					case 'DEPENDENT SUBQUERY':   $select_type = "<span class='red'>{$select_type}</span>"; break;
				}

				switch ($type) {
					case 'ALL':             $type = "<strong class='red'>{$type}</strong>"; break;
					case 'index':           $type = "<span class='red'>{$type}</span>"; break;
					case 'system':
					case 'const':           $type = "<strong class='green'>{$type}</strong>"; break;
					case 'eq_ref':
					case 'unique_subquery': $type = "<span class='green'>{$type}</span>"; break;
					case 'ref':
					case 'ref_or_null':
					case 'fulltext':
					case 'index_subquery':  $type = "<span class='darkcyan'>{$type}</span>"; break;
					case 'range':
					case 'index_merge':     $type = "<strong class='orange'>{$type}</strong>"; break;
				}

				if (empty($key)) {
					$key = '<strong class="red">&mdash;</strong>';
				}

				if (empty($possible_keys)) {
					$possible_keys = '<strong class="red">&mdash;</strong>';
				}

				if ($key_len <= 8)       { $key_len = "<strong class='green'>{$key_len}</strong>"; }
				elseif ($key_len <= 16)  { $key_len = "<span class='green'>{$key_len}</span>"; }
				elseif ($key_len <= 32)  { $key_len = "<span class='orange'>{$key_len}</span>"; }
				elseif ($key_len <= 100) { $key_len = "<span class='red'>{$key_len}</span>"; }
				else                     { $key_len = "<strong class='red'>{$key_len}</strong>"; }

				if ($rows < 500) {}
				elseif ($rows < 1000) { $rows = "<span class='orange'>{$rows}</span>"; }
				elseif ($rows < 5000) { $rows = "<span class='red'>{$rows}</span>"; }
				else                  { $rows = "<strong class='red'>{$rows}</strong>"; }

				$e = array_map('trim', explode(';', $extra));
				if (!empty($e)) {
					foreach ($e as $thekey => $v) {
						switch ($v) {
							case 'No tables':
							case 'Not exists':
							case 'Select tables optimized away':
							case 'Impossible WHERE noticed after reading const tables':
								$v = "<strong class='green'>{$v}</strong>";
								break;

							case 'Using index':
							case 'Using index for group-by':
							case 'Using where with pushed condition':
								$v = "<span class='green'>{$v}</span>";
								break;

							case 'Distinct':
								$v = "<span class='darkcyan'>{$v}</span>";
								break;

							case 'Full scan on NULL key':
								$v = "<span class='orange'>{$v}</span>";
								break;

							case 'Using filesort':
							case 'Using temporary':
								$v = "<strong class='red'>{$v}</strong>";
								break;

							default:
								if ('Range checked for each record' == substr($v, 0, strlen('Range checked for each record'))) {
									$v = "<strong class='orange'>{$v}</strong>";
								}
						}

						$e[$thekey] = $v;
					}

					$extra = join('; ', $e);
				}

				$entry[$k] = $x;
			}

			return $entry;
		}

		/**
		 * @desc Converts an array to XHTML table
		 * @param array $x
		 * @return string
		 */
		protected static function resToTable($x)
		{
			$html = '';
			if (!empty($x)) {
				$html = '<table class="sqldebug res explain" cellpadding="2" cellspacing="1"><thead><tr>';
				foreach ($x[0] as $key => $value) {
					$html .= '<th>' . esc_attr($key) . '</th>';
				}
				$html .= '</tr></thead><tbody>';
				foreach ($x as $entry) {
					$html .= '<tr>';
					foreach ($entry as $value) {
						$html .= '<td>' . $value . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			}
			return $html;
		}

	}
?>