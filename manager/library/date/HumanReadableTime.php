<?php

/**
 * class that provides methods to convert human readable time / interval length
 * expressions into other formats
 *
 * @author markus
 * $Id: HumanReadableTime.php 92 2010-03-10 11:43:15Z markus $
 */

require_once(LIBRARY_PATH . '/date/exception.HumanReadableTimeException.php');

class HumanReadableTime {
	/**
	 * normalize an HRT string, convert from HRT to seconds and then convert back to
	 * HRT
	 * @param string $hrt
	 * @param string $maxunit
	 * @return string
	 */
	public static function NormalizeHRT($hrt, $maxunit = 'w') {
		return self::Seconds2HR(self::HR2Seconds($hrt), $maxunit);
	}

	/**
	 * convert string / interger which contains an interval length to
	 * human readable format (1w2d7h)
	 *
	 * if $maxunit is set, it defines the biggest unit in output (i.e. $maxunit = 'h' will
	 * allow only hms)
	 *
	 * @param string|integer $seconds
	 * @param string $maxunit
	 * @return string
	 */
	public static function Seconds2HR($seconds, $maxunit = 'w') {
		$maxunit = trim(strtolower($maxunit));
		$allowed = array('w' => 0, 'd' => 0, 'h' => 0, 'm' => 0, 's' => 0);
		if (!in_array($maxunit, array_keys($allowed), true))
			throw new HumanReadableTimeException('illegal value for maxunit: "' . $maxunit . '"');
		foreach ($allowed as $key => $value) {
			if ($maxunit == $key)
				break;
			unset($allowed[$key]);
		}

		$seconds = intval($seconds);
		$hrt = '';
		foreach ($allowed as $key => $value) {
			switch ($key) {
				case 'w':
					$tmp = intval($seconds / (7*86400));
					if ($tmp > 0)
						$seconds %= (7*86400);
					$allowed[$key] += $tmp;
					break;
				case 'd':
					$tmp = intval($seconds / (86400));
					if ($tmp > 0)
						$seconds %= (86400);
					$allowed[$key] += $tmp;
					break;
				case 'h':
					$tmp = intval($seconds / (3600));
					if ($tmp > 0)
						$seconds %= (3600);
					$allowed[$key] += $tmp;
					break;
				case 'm':
					$tmp = intval($seconds / (60));
					if ($tmp > 0)
						$seconds %= (60);
					$allowed[$key] += $tmp;
					break;
				case 's':
					$allowed[$key] += $seconds;
					break;
			}
		}

		$hrt = '';
		foreach ($allowed as $key => $value) {
			if ($value > 0)
				$hrt .= sprintf('%d%s', $value, $key);
		}
		return $hrt;
	}

	/**
	 * parse a string of 3h2m7s and return the number of seconds as integer
	 * add "s" to the end of the number if $addsecond is set to true
	 * @param string $hr
	 * @param boolean $addsecond
	 * @return integer|string
	 */
	public static function HR2Seconds($hr, $addsecond = false) {
		$hr = trim($hr);
		if ($hr == '') {
			if ($addsecond === true)
				return '0s';
			else
				return 0;
		}

		$hr = strtolower($hr);

		$matches = array();
		if (preg_match_all('/([0-9]*)([wdhms])/', $hr, $matches, PREG_SET_ORDER) > 0) {
			$interval = 0;
			for ($i = 0; $i < count($matches); $i++) {
				switch ($matches[$i][2]) {
					case 'w':
						$interval += $matches[$i][1] * 7 * 86400;
						break;
					case 'd':
						$interval += $matches[$i][1] * 86400;
						break;
					case 'h':
						$interval += $matches[$i][1] * 3600;
						break;
					case 'm':
						$interval += $matches[$i][1] * 60;
						break;
					case 's':
						$interval += $matches[$i][1];
						break;
				}
			}
			if ($addsecond === true)
				return sprintf('%ds', $interval);
			else
				return $interval;
		}

		if ($addsecond === true)
			return '0s';
		else
			return 0;
	}
}
