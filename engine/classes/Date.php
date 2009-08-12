<?php

class Date {
	
	var $timestamp;
	var $gmtTimestamp;
	var $displayTimezone;
	var $now;
	var $isValid;
	
	/*
	 * Constructor
	 */
	
	function Date ($time, $isLocal = false) {
		global $_JAM;
		
		if ($_COOKIE['timezone']) {
			$this->displayTimezone = $_COOKIE['timezone'];
		} else {
			$this->displayTimezone = $_JAM->projectConfig['defaultDisplayTime'];
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
			$timeOffset = ($this->displayTimezone - $_JAM->serverConfig['serverTime']) * 60 * 60;
			$this->timestamp = $timestamp + $timeOffset;
		}
		
		// Determine GMT time
		$this->gmtTimestamp = $this->timestamp + (-$_JAM->serverConfig['serverTime'] * 60 * 60);
		
		// Get local database time
		$this->now = strtotime($_JAM->databaseTime);
	}
	
	/*
	 * Static
	 */
	
	function PadWithZeros($string) {
		return str_pad($string, 2, '0', STR_PAD_LEFT);
	}
	
	function ValidateTime($time) {
		// Checks that $time is a valid time in HH:MM format
		$hour = substr($time, 0, 2);
		$minutes = substr($time, -2);
		if (($hour >= 0 && $hour < 24) && ($minutes >= 0 && $minutes < 60) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/*
	 * Public
	 */
	
	function Offset($amount, $unit) {
		// Offset the time by given amount
		switch ($unit) {
			case 'years':
				$offsetFactor = 365 * 24 * 60 * 60;
				break;
			case 'months':
				$offsetFactor = 30 * 24 * 60 * 60;
				break;
			case 'weeks':
				$offsetFactor = 7 * 24 * 60 * 60;
				break;
			case 'days':
				$offsetFactor = 24 * 60 * 60;
				break;
			case 'hours':
				$offsetFactor = 60 * 60;
				break;
			case 'minutes':
				$offsetFactor = 60;
				break;
			case 'seconds':
			default:
				$offsetFactor = 1;
				break;
		}
		$offsetAmount = $amount * $offsetFactor;
		
		if ($this->timestamp += $offsetAmount) {
			return true;
		} else {
			return false;
		}
	}
	
	function DateRange($days) {
		global $_JAM;
		$endTimestamp = $this->timestamp + (($days - 1) * 24 * 60 * 60);
		$endDate = new Date($endTimestamp);
		
		$startDay = $this->GetDay();
		$startMonth = $_JAM->strings['months'][$this->GetMonth()];
		$startYear = $this->GetYear();
		
		$endDay = $endDate->GetDay();
		$endMonth = $_JAM->strings['months'][$endDate->GetMonth()];
		$endYear = $endDate->GetYear();
		
		// Check whether date range begins and ends in the same month and year
		$sameMonth = ($startMonth == $endMonth);
		$sameYear = ($startYear == $endYear);
		
		// Set date separator
		$separator = ' '. $_JAM->strings['words']['to'] .' ';
		
		// If dates aren't in the same year, just use LongDate() for both the start and end date
		if (!$sameYear) {
			return $this->LongDate() . $separator . $endDate->LongDate();
		}
		
		// Otherwise, format date according to language
		switch ($_JAM->language) {
			case 'en':
				if ($sameMonth) {
					return $startMonth .' '. $startDay . $separator . $endDay .' '. $startYear;
				} else {
					return $startMonth .' '. $startDay . $separator . $endMonth .' '. $endDay .' '. $startYear;
				}
				break;
			default:
				if ($sameMonth) {
					return $startDay . $separator . $endDay .' '. $startMonth .' '. $startYear;
				} else {
					return $startDay .' '. $startMonth . $separator . $endDay .' '. $endMonth .' '. $startYear;
				}
				break;
		}
		
		return false;
	}
	
	function LongDate() {
		global $_JAM;
		$day = date('j',$this->timestamp);
		$month = $_JAM->strings['months'][(int) date('n',$this->timestamp)];
		$year = date('Y',$this->timestamp);
		switch ($_JAM->language) {
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
		global $_JAM;
		$today = mktime(0, 0, 0, date("m",$this->now), date("d",$this->now), date("Y",$this->now));
		$theDay = mktime(0, 0, 0, date("m",$this->timestamp), date("d",$this->timestamp), date("Y",$this->timestamp));
		$daysOffset = ($today - $theDay) / (60 * 60 * 24);
		if ($daysOffset === 0) {
			return $_JAM->strings['relativeDates']['today'];
		} elseif ($daysOffset == 1) {
			return $_JAM->strings['relativeDates']['yesterday'];
		} elseif ($daysOffset < 30 * 11) {
			// Omit year if less than ~11 months have passed
			$day = date('j', $this->timestamp);
			$month = $_JAM->strings['months'][(int) date('n', $this->timestamp)];
			switch ($_JAM->language) {
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
	
	function SmartDateAndTime() {
		global $_JAM;
		return $this->SmartDate() .', '. $this->Time24();
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
		global $_JAM;
		$timeOffset = ($_JAM->serverConfig['serverTime'] - $this->displayTimezone) * 60 * 60;
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
	
}

?>
