<?php
	require_once('class.AbstractLogEntry.php');

	final class Logger
	{
		public $enabled = true;
		private $log = array();

		/**
		 * @return Logger
		 */
		public static function instance()
		{
			static $self = false;
			if (!$self) {
				$self = new Logger();
			}

			return $self;
		}

		public static function getTime()
		{
			$mtime = explode(' ', microtime());
			return (float)$mtime[1] + (float)$mtime[0];
		}

		private function __construct()
		{
		}

		public function addToLog(AbstractLogEntry $record)
		{
			if ($this->enabled) {
				$this->log[] = $record;
			}
		}

		public function clearLog()
		{
			$this->log = array();
		}

		public function dump($save = false)
		{
			$tt  = 0;
			$cnt = count($this->log);
			if (!$cnt) {
				return '';
			}

			ob_start();
			$entry = $this->log[0];

			echo <<< delimiter
<table cellpadding='2' class='sqldebug'><thead>
delimiter;
			$entry->renderHeader();
			echo <<< delimiter
</thead><tbody style="vertical-align: top">
delimiter;

			foreach ($this->log as $no => $entry)
			{
				$tt += $entry->time;
				$entry->render($no+1);
			}

			$tt = round($tt, 5);

			echo <<< delimiter
	<tr>
		<td colspan='4' style='font-weight: bold'>Total $cnt queries, time taken:</td>
		<td>$tt</td>
	</tr>
</tbody>
</table>
delimiter;

			if ($save) {
				$s = ob_get_contents();
				ob_end_clean();
				return $s;
			}

			ob_end_flush();
			return '';
		}
	}
?>