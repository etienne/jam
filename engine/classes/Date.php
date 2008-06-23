<?php

class Date {
	
	var $timestamp;
	var $gmtTimestamp;
	var $displayTimezone;
	var $now;
	var $isValid;
	
	function Date ($time, $isLocal = false) {
		global $_JAG;
		
		if ($_COOKIE['timezone']) {
			$this->displayTimezone = $_COOKIE['timezone'];
		} else {
			$this->displayTimezone = $_JAG['project']['defaultDisplayTime'];
		}
		
		if (strpos($time, ' ') !== false) {
			// Time is likely given as a string
			$timestamp = strtotime($time);
		} else {
			// Time will be interpreted as a timestamp
			$timestamp = $time;
		}
		
		if ($timestamp && $timestamp != -1) {
			// Date is probably valid
			$this->isValid = true;
		}
		
		// We internally store the date in its localized form
		if ($isLocal) {
			$this->timestamp = $timestamp;
		} else {
			$timeOffset = ($this->displayTimezone - $_JAG['server']['serverTime']) * 60 * 60;
			$this->timestamp = $timestamp + $timeOffset;
		}
		
		// Determine GMT time
		$this->gmtTimestamp = $this->timestamp + (-$_JAG['server']['serverTime'] * 60 * 60);
		
		// Get local database time
		$this->now = strtotime($_JAG['databaseTime']);
	}
	
	function LongDate() {
		global $_JAG;
		$day = date('j',$this->timestamp);
		$month = $_JAG['strings']['months'][(int) date('n',$this->timestamp)];
		$year = date('Y',$this->timestamp);
		switch ($_JAG['language']) {
			case 'en':
				return $month . ' ' . $day . ' ' . $year;
				break;
			default:
				return $day . ' ' . $month . ' ' . $year;
				break;
		}
	}
	
	function SmartDate() {
		/* Takes a UNIX-style timestamp and return a smartly formatted, localized date */
		global $_JAG;
		$today = mktime(0, 0, 0, date("m",$this->now), date("d",$this->now), date("Y",$this->now));
		$theDay = mktime(0, 0, 0, date("m",$this->timestamp), date("d",$this->timestamp), date("Y",$this->timestamp));
		$daysOffset = ($today - $theDay) / (60 * 60 * 24);
		if ($daysOffset === 0) {
			return $_JAG['strings']['relativeDates']['today'];
		} elseif ($daysOffset == 1) {
			return $_JAG['strings']['relativeDates']['yesterday'];
		} elseif ($daysOffset < 30 * 11) {
			// Omit year if less than ~11 months have passed
			$day = date('j', $this->timestamp);
			$month = $_JAG['strings']['months'][(int) date('n', $this->timestamp)];
			switch ($_JAG['language']) {
				case 'en':
					return $month .' '. $day;
					break;
				default:
					return $day .' '. $month;
					break;
			}
		} else {
			return $this->LongDate();
		}
	}
	
	function Time24() {
		return date('G:i', $this->timestamp);
	}
	
	function Time() {
		return date('g:i A',$this->timestamp);
	}
	
	function ShortTime() {
		$formatString = (date('i',$this->timestamp) != '00') ? 'g:i A' : 'g A';
		return date($formatString, $this->timestamp);
	}
	
	function ShortDate() {
		return date('Y.m.d', $this->timestamp);
	}
	
	function ShortDateAndTime() {
		return date('Y.m.d g:i A', $this->timestamp);
	}
	
	function HTTPTimestamp() {
		return date('D, d M Y H:i:s \G\M\T', $this->gmtTimestamp);
	}
	
	function RFC3339Timestamp() {
		return date('Y-m-d\TH:i:s\Z', $this->gmtTimestamp);
	}
	
	function DatabaseTimestamp() {
		global $_JAG;
		$timeOffset = ($_JAG['server']['serverTime'] - $this->displayTimezone) * 60 * 60;
		$databaseTime = $this->timestamp + $timeOffset;
		return date('Y-m-d H:i:s', $databaseTime);
	}
	
	function GetYear() {
		return date('Y', $this->timestamp);
	}
	
	function GetMonth() {
		return date('n', $this->timestamp);
	}
	
	function GetDay() {
		return date('j', $this->timestamp);
	}
	
	function GetHour() {
		return date('G', $this->timestamp);
	}
	
	function GetMinutes() {
		return date('i', $this->timestamp);
	}
	
	function GetSeconds() {
		return date('s', $this->timestamp);
	}
	
	function PadWithZeros($string) {
		return str_pad($string, 2, '0', STR_PAD_LEFT);
	}
}

?>
