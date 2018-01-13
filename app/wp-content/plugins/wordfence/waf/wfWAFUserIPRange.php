<?php

/**
 *
 */
class wfWAFUserIPRange {

	/**
	 * @var string|null
	 */
	private $ip_string;

	/**
	 * @param string|null $ip_string
	 */
	public function __construct($ip_string = null) {
		$this->setIPString($ip_string);
	}

	/**
	 * Check if the supplied IP address is within the user supplied range.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function isIPInRange($ip) {
		$ip_string = $this->getIPString();

		// IPv4 range
		if (strpos($ip_string, '.') !== false && strpos($ip, '.') !== false) {
			// IPv4-mapped-IPv6
			if (preg_match('/:ffff:([^:]+)$/i', $ip_string, $matches)) {
				$ip_string = $matches[1];
			}
			if (preg_match('/:ffff:([^:]+)$/i', $ip, $matches)) {
				$ip = $matches[1];
			}
			
			// Range check
			if (preg_match('/\[\d+\-\d+\]/', $ip_string)) {
				$IPparts = explode('.', $ip);
				$whiteParts = explode('.', $ip_string);
				$mismatch = false;
				if (count($whiteParts) != 4 || count($IPparts) != 4) {
					return false;
				}
				
				for ($i = 0; $i <= 3; $i++) {
					if (preg_match('/^\[(\d+)\-(\d+)\]$/', $whiteParts[$i], $m)) {
						if ($IPparts[$i] < $m[1] || $IPparts[$i] > $m[2]) {
							$mismatch = true;
						}
					} else if ($whiteParts[$i] != $IPparts[$i]) {
						$mismatch = true;
					}
				}
				if ($mismatch === false) {
					return true; // Is whitelisted because we did not get a mismatch
				}
			} else if ($ip_string == $ip) {
				return true;
			}

			// IPv6 range
		} else if (strpos($ip_string, ':') !== false && strpos($ip, ':') !== false) {
			$ip = strtolower(wfWAFUtils::expandIPv6Address($ip));
			$ip_string = strtolower(self::expandIPv6Range($ip_string));
			if (preg_match('/\[[a-f0-9]+\-[a-f0-9]+\]/i', $ip_string)) {
				$IPparts = explode(':', $ip);
				$whiteParts = explode(':', $ip_string);
				$mismatch = false;
				if (count($whiteParts) != 8 || count($IPparts) != 8) {
					return false;
				}
				
				for ($i = 0; $i <= 7; $i++) {
					if (preg_match('/^\[([a-f0-9]+)\-([a-f0-9]+)\]$/i', $whiteParts[$i], $m)) {
						$ip_group = hexdec($IPparts[$i]);
						$range_group_from = hexdec($m[1]);
						$range_group_to = hexdec($m[2]);
						if ($ip_group < $range_group_from || $ip_group > $range_group_to) {
							$mismatch = true;
							break;
						}
					} else if ($whiteParts[$i] != $IPparts[$i]) {
						$mismatch = true;
						break;
					}
				}
				if ($mismatch === false) {
					return true; // Is whitelisted because we did not get a mismatch
				}
			} else if ($ip_string == $ip) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a set of where clauses to use in MySQL.
	 *
	 * @param string $column
	 * @return false|null|string
	 */
	public function toSQL($column = 'ip') {
		/** @var wpdb $wpdb */
		global $wpdb;
		$ip_string = $this->getIPString();

		if (strpos($ip_string, '.') !== false && preg_match('/\[\d+\-\d+\]/', $ip_string)) {
			$whiteParts = explode('.', $ip_string);
			$sql = "(SUBSTR($column, 1, 12) = LPAD(CHAR(0xff, 0xff), 12, CHAR(0)) AND ";

			for ($i = 0, $j = 24; $i <= 3; $i++, $j -= 8) {
				// MySQL can only perform bitwise operations on integers
				$conv = sprintf('CAST(CONV(HEX(SUBSTR(%s, 13, 8)), 16, 10) as UNSIGNED INTEGER)', $column);
				if (preg_match('/^\[(\d+)\-(\d+)\]$/', $whiteParts[$i], $m)) {
					$sql .= $wpdb->prepare("$conv >> $j & 0xFF BETWEEN %d AND %d", $m[1], $m[2]);
				} else {
					$sql .= $wpdb->prepare("$conv >> $j & 0xFF = %d", $whiteParts[$i]);
				}
				$sql .= ' AND ';
			}
			$sql = substr($sql, 0, -5) . ')';
			return $sql;
			
		} else if (strpos($ip_string, ':') !== false) {
			$ip_string = strtolower(self::expandIPv6Range($ip_string));
			if (preg_match('/\[[a-f0-9]+\-[a-f0-9]+\]/i', $ip_string)) {
				$whiteParts = explode(':', $ip_string);
				$sql = '(';
				
				for ($i = 0; $i <= 7; $i++) {
					// MySQL can only perform bitwise operations on integers
					$conv = sprintf('CAST(CONV(HEX(SUBSTR(%s, %d, 8)), 16, 10) as UNSIGNED INTEGER)', $column, $i < 4 ? 1 : 9);
					$j = 16 * (3 - ($i % 4));
					if (preg_match('/^\[([a-f0-9]+)\-([a-f0-9]+)\]$/i', $whiteParts[$i], $m)) {
						$sql .= $wpdb->prepare("$conv >> $j & 0xFFFF BETWEEN 0x%x AND 0x%x", hexdec($m[1]), hexdec($m[2]));
					} else {
						$sql .= $wpdb->prepare("$conv >> $j & 0xFFFF = 0x%x", hexdec($whiteParts[$i]));
					}
					$sql .= ' AND ';
				}
				$sql = substr($sql, 0, -5) . ')';
				return $sql;
			}
		}
		
		return $wpdb->prepare("($column = %s)", wfWAFUtils::inet_pton($ip_string));
	}

	/**
	 * Expand a compressed printable range representation of an IPv6 address.
	 *
	 * @todo Hook up exceptions for better error handling.
	 * @todo Allow IPv4 mapped IPv6 addresses (::ffff:192.168.1.1).
	 * @param string $ip_range
	 * @return string
	 */
	public static function expandIPv6Range($ip_range) {
		$colon_count = substr_count($ip_range, ':');
		$dbl_colon_count = substr_count($ip_range, '::');
		if ($dbl_colon_count > 1) {
			return false;
		}
		$dbl_colon_pos = strpos($ip_range, '::');
		if ($dbl_colon_pos !== false) {
			$ip_range = str_replace('::', str_repeat(':0000',
					(($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip_range) - 2) ? 9 : 8) - $colon_count) . ':', $ip_range);
			$ip_range = trim($ip_range, ':');
		}
		$colon_count = substr_count($ip_range, ':');
		if ($colon_count != 7) {
			return false;
		}

		$groups = explode(':', $ip_range);
		$expanded = '';
		foreach ($groups as $group) {
			if (preg_match('/\[([a-f0-9]{1,4})\-([a-f0-9]{1,4})\]/i', $group, $matches)) {
				$expanded .= sprintf('[%s-%s]', str_pad(strtolower($matches[1]), 4, '0', STR_PAD_LEFT), str_pad(strtolower($matches[2]), 4, '0', STR_PAD_LEFT)) . ':';
			} else if (preg_match('/[a-f0-9]{1,4}/i', $group)) {
				$expanded .= str_pad(strtolower($group), 4, '0', STR_PAD_LEFT) . ':';
			} else {
				return false;
			}
		}
		return trim($expanded, ':');
	}

	/**
	 * @return bool
	 */
	public function isValidRange() {
		return $this->isValidIPv4Range() || $this->isValidIPv6Range();
	}

	/**
	 * @return bool
	 */
	public function isValidIPv4Range() {
		$ip_string = $this->getIPString();
		if (preg_match_all('/(\d+)/', $ip_string, $matches) > 0) {
			foreach ($matches[1] as $match) {
				$group = (int) $match;
				if ($group > 255 || $group < 0) {
					return false;
				}
			}
		}

		$group_regex = '([0-9]{1,3}|\[[0-9]{1,3}\-[0-9]{1,3}\])';
		return preg_match('/^' . str_repeat("$group_regex.", 3) . $group_regex . '$/i', $ip_string) > 0;
	}

	/**
	 * @return bool
	 */
	public function isValidIPv6Range() {
		$ip_string = $this->getIPString();
		if (strpos($ip_string, '::') !== false) {
			$ip_string = self::expandIPv6Range($ip_string);
		}
		if (!$ip_string) {
			return false;
		}
		$group_regex = '([a-f0-9]{1,4}|\[[a-f0-9]{1,4}\-[a-f0-9]{1,4}\])';
		return preg_match('/^' . str_repeat("$group_regex:", 7) . $group_regex . '$/i', $ip_string) > 0;
	}


	/**
	 * @return string|null
	 */
	public function getIPString() {
		return $this->ip_string;
	}

	/**
	 * @param string|null $ip_string
	 */
	public function setIPString($ip_string) {
		$this->ip_string = strtolower(preg_replace('/[\x{2013}-\x{2015}]/u', '-', $ip_string)); //Replace em-dash, en-dash, and horizontal bar with a regular dash
	}
}
