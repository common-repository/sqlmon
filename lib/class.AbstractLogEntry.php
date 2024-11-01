<?php
	abstract class AbstractLogEntry
	{
		public $time = 0;

		abstract public function renderHeader();

		abstract public function render($row_no);
	}
?>