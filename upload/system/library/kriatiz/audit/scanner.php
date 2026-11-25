<?php
/**
 * KRIATIZ Аудит — ядро сканирования.
 *
 * Проверка идёт по фазам и частями: каждый AJAX-запрос обрабатывает порцию работы
 * не дольше BUDGET секунд и сохраняет состояние. Так проверка не падает по времени
 * на больших сайтах и продолжается после обрыва.
 *
 * Модуль только читает файлы и базу. Ничего не меняет, не отправляет данные наружу.
 */
require_once(__DIR__ . '/security.php');
require_once(__DIR__ . '/seo.php');

class KriatizAuditScanner {
	const BUDGET = 3.0;

	public static $phases = array('config', 'core', 'files', 'database', 'frontend', 'seo', 'lang');

	public $registry;
	public $db;
	public $config;
	public $root;
	public $state;
	public $issues = array();
	private $signatures;

	public function __construct($registry, $state) {
		$this->registry = $registry;
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
		$this->root = rtrim(str_replace('\\', '/', realpath(DIR_SYSTEM . '..')), '/') . '/';
		$this->state = $state;
	}

	/* ------------------------------------------------------------------ */
	/* Управление фазами                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * Выполнить порцию работы. Возвращает true, когда вся проверка закончена.
	 */
	public function step() {
		$start = microtime(true);

		while (microtime(true) - $start < self::BUDGET) {
			if (!isset($this->state['phase'])) {
				$this->state['phase'] = 0;
			}

			if ($this->state['phase'] >= count(self::$phases)) {
				return true;
			}

			$phase = self::$phases[$this->state['phase']];
			$done = $this->runPhase($phase, $start);

			if ($done) {
				$this->state['phase']++;
				$this->state['cursor'] = array();
			}
		}

		return $this->state['phase'] >= count(self::$phases);
	}

	public function progress() {
		$total = count(self::$phases);
		$phase = isset($this->state['phase']) ? min($this->state['phase'], $total) : 0;
		$inner = isset($this->state['cursor']['progress']) ? (float)$this->state['cursor']['progress'] : 0;

		return array(
			'phase'   => $phase < $total ? self::$phases[$phase] : 'done',
			'percent' => (int)min(100, round(($phase + min(0.99, $inner)) / $total * 100)),
			'files'   => isset($this->state['stats']['files']) ? (int)$this->state['stats']['files'] : 0,
			'rows'    => isset($this->state['stats']['rows']) ? (int)$this->state['stats']['rows'] : 0,
		);
	}

	private function runPhase($phase, $start) {
		if (!isset($this->state['cursor']) || !is_array($this->state['cursor'])) {
			$this->state['cursor'] = array();
		}

		$c = &$this->state['cursor'];

		switch ($phase) {
			case 'config':   return KriatizAuditSecurity::config($this, $c);
			case 'core':     return KriatizAuditSecurity::core($this, $c, $start);
			case 'files':    return KriatizAuditSecurity::files($this, $c, $start);
			case 'database': return KriatizAuditSecurity::database($this, $c, $start);
			case 'frontend': return KriatizAuditSecurity::frontend($this, $c, $start);
			case 'seo':      return KriatizAuditSeo::seo($this, $c);
			case 'lang':     return KriatizAuditSeo::lang($this, $c);
		}

		return true;
	}

	public function timeLeft($start) {
		return microtime(true) - $start < self::BUDGET;
	}

	public function stat($key, $add = 1) {
		if (!isset($this->state['stats'][$key])) {
			$this->state['stats'][$key] = 0;
		}

		$this->state['stats'][$key] += $add;
	}

	/* ------------------------------------------------------------------ */
	/* Находки                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * @param string $block   security | seo | lang
	 * @param string $code    код проверки (тексты в языковом файле check_<code>_*)
	 * @param string $level   critical | warning | info | ok
	 * @param string $target  файл, таблица:id или пусто
	 * @param array  $data    подробности (фрагмент, числа, списки)
	 */
	public function issue($block, $code, $level, $target = '', $data = array()) {
		$this->issues[] = array(
			'block'  => $block,
			'code'   => $code,
			'level'  => $level,
			'target' => (string)$target,
			'data'   => $data,
			'hash'   => md5($block . '|' . $code . '|' . $target),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Сигнатуры                                                           */
	/* ------------------------------------------------------------------ */

	public function signatures() {
		if ($this->signatures === null) {
			$raw = @file_get_contents(__DIR__ . '/signatures.dat');
			$this->signatures = $raw ? json_decode(base64_decode($raw), true) : array();

			if (!is_array($this->signatures)) {
				$this->signatures = array();
			}
		}

		return $this->signatures;
	}

	/**
	 * Проверить текст сигнатурами группы. Возвращает первую найденную: id, level, фрагмент, строка.
	 */
	public function match($group, $text) {
		$sigs = $this->signatures();

		if (empty($sigs[$group])) {
			return null;
		}

		$best = null;

		foreach ($sigs[$group] as $sig) {
			$hit = null;

			if (!empty($sig['known_ok'])) {
				// Скрытый iframe известного сервиса (Google Tag Manager, Facebook) — норма
				if (@preg_match_all($sig['re'], $text, $all, PREG_OFFSET_CAPTURE)) {
					foreach ($all[0] as $one) {
						if (!preg_match('/src\s*=\s*[\'"]?https?:\/\/([a-z0-9.\-]+)/i', $one[0], $h) || !$this->knownDomain($h[1])) {
							$hit = $one;
							break;
						}
					}
				}
			} elseif (@preg_match($sig['re'], $text, $m, PREG_OFFSET_CAPTURE)) {
				$hit = $m[0];
			}

			if ($hit) {
				$found = array(
					'id'      => $sig['id'],
					'level'   => $sig['level'],
					'line'    => substr_count($text, "\n", 0, $hit[1]) + 1,
					'snippet' => self::snippet($text, $hit[1], strlen($hit[0])),
				);

				if ($sig['level'] === 'critical') {
					return $found;
				}

				if (!$best) {
					$best = $found;
				}
			}
		}

		return $best;
	}

	/** HTML (база, страницы витрины, шаблоны): JS-сигнатуры плюс HTML-сигнатуры. */
	public function matchHtml($text) {
		$js = $this->match('js', $text);

		if ($js && $js['level'] === 'critical') {
			return $js;
		}

		$html = $this->match('html', $text);

		return $html ? $html : $js;
	}

	public function spamWords() {
		$sigs = $this->signatures();

		return isset($sigs['spam_words']) ? $sigs['spam_words'] : 'casino|viagra|cialis|porn';
	}

	public function knownDomain($host) {
		static $list = null;

		if ($list === null) {
			$sigs = $this->signatures();
			$list = isset($sigs['known_domains']) ? preg_split('/\s+/', trim($sigs['known_domains'])) : array();
		}

		$host = strtolower($host);
		$own = strtolower((string)parse_url(HTTP_CATALOG, PHP_URL_HOST));

		if ($host === $own || ($own && substr($host, -strlen('.' . $own)) === '.' . $own)) {
			return true;
		}

		foreach ($list as $domain) {
			if ($domain && ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain)) {
				return true;
			}
		}

		return false;
	}

	/** Короткий фрагмент вокруг находки, безопасный для показа. */
	public static function snippet($text, $offset, $length) {
		$from = max(0, $offset - 60);
		$part = substr($text, $from, min(240, $length + 120));
		$part = preg_replace('/\s+/', ' ', $part);

		if (function_exists('mb_convert_encoding')) {
			$part = @mb_convert_encoding($part, 'UTF-8', 'UTF-8');
		}

		return trim($part);
	}

	/* ------------------------------------------------------------------ */
	/* HTTP к своему сайту                                                 */
	/* ------------------------------------------------------------------ */

	public function catalogUrl() {
		$https = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG;

		return rtrim($this->config->get('config_secure') ? $https : HTTP_CATALOG, '/') . '/';
	}

	/**
	 * Запрос к собственному магазину. Если сервер не видит себя по внешнему адресу — через 127.0.0.1.
	 */
	public function fetch($url, $agent = 'browser', $follow = false) {
		$agents = array(
			'browser' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
			'bot'     => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'mobile'  => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36',
		);
		$ua = isset($agents[$agent]) ? $agents[$agent] : $agent;
		$result = array('code' => 0, 'location' => '', 'body' => '', 'headers' => array(), 'error' => '');

		if (!ini_get('allow_url_fopen')) {
			$result['error'] = 'allow_url_fopen = Off';

			return $result;
		}

		$parts = parse_url($url);
		$host = isset($parts['host']) ? $parts['host'] : '';
		$port = isset($parts['port']) ? (int)$parts['port'] : (isset($parts['scheme']) && $parts['scheme'] == 'https' ? 443 : 80);
		$host_header = $host . (isset($parts['port']) ? ':' . $port : '');
		$attempts = array(
			array('url' => $url, 'host' => ''),
			array('url' => preg_replace('#^(https?://)[^/:]+#i', '${1}127.0.0.1', $url), 'host' => $host_header),
		);

		if (in_array($host, array('localhost', '127.0.0.1'))) {
			$attempts[] = array('url' => preg_replace('#^(https?://[^/:]+):\d+#', '$1', $url), 'host' => $host . ':' . $port);
		}

		foreach ($attempts as $attempt) {
			$headers = array('User-Agent: ' . $ua, 'Accept-Language: uk,ru;q=0.8,en;q=0.6', 'Connection: close');

			if ($attempt['host']) {
				$headers[] = 'Host: ' . $attempt['host'];
			}

			$context = stream_context_create(array(
				'http' => array('method' => 'GET', 'header' => implode("\r\n", $headers), 'follow_location' => $follow ? 1 : 0, 'max_redirects' => $follow ? 5 : 1, 'ignore_errors' => true, 'timeout' => 12),
				'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false, 'peer_name' => $host, 'SNI_enabled' => true),
			));

			$http_response_header = array();
			$warning = '';
			set_error_handler(function ($errno, $errstr) use (&$warning) {
				$warning = $errstr;

				return true;
			});
			$body = file_get_contents($attempt['url'], false, $context, 0, 2097152);
			restore_error_handler();

			if ($body === false && !$http_response_header) {
				$result['error'] = $warning ? preg_replace('/^file_get_contents\([^)]*\):\s*/', '', $warning) : 'connection failed';
				continue;
			}

			foreach ($http_response_header as $line) {
				if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
					$result['code'] = (int)$m[1];
					$result['headers'] = array();
				} elseif (strpos($line, ':') !== false) {
					list($name, $value) = explode(':', $line, 2);
					$result['headers'][strtolower(trim($name))] = trim($value);

					if (strtolower(trim($name)) === 'location') {
						$result['location'] = trim($value);
					}
				}
			}

			$result['body'] = (string)$body;
			$result['error'] = '';

			return $result;
		}

		return $result;
	}

	/* ------------------------------------------------------------------ */
	/* Вспомогательное                                                     */
	/* ------------------------------------------------------------------ */

	public function tableExists($table) {
		static $cache = array();

		if (!isset($cache[$table])) {
			$cache[$table] = (bool)$this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'")->num_rows;
		}

		return $cache[$table];
	}

	public function columns($table) {
		static $cache = array();

		if (!isset($cache[$table])) {
			$cache[$table] = array();

			if ($this->tableExists($table)) {
				foreach ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . self::ident($table) . "`")->rows as $row) {
					$cache[$table][] = $row['Field'];
				}
			}
		}

		return $cache[$table];
	}

	public static function lower($text) {
		return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
	}

	/** Имя таблицы или колонки для SQL: только латиница, цифры и подчёркивание. */
	public static function ident($name) {
		return preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
	}

	public static function legacy() {
		return version_compare(VERSION, '3.0.0.0', '<');
	}

	/** Путь относительно корня сайта. */
	public function rel($path) {
		$path = str_replace('\\', '/', $path);

		return strpos($path, $this->root) === 0 ? substr($path, strlen($this->root)) : $path;
	}

	/** Итоговые оценки по блокам 0–100. */
	public static function scores($issues, $applicable = array('security' => true, 'seo' => true, 'lang' => true)) {
		$weights = array('critical' => 20, 'warning' => 6, 'info' => 1);
		$caps = array('critical' => 100, 'warning' => 40, 'info' => 10);
		$minus = array('security' => array(), 'seo' => array(), 'lang' => array());

		foreach ($issues as $issue) {
			if (!isset($weights[$issue['level']]) || !isset($minus[$issue['block']])) {
				continue;
			}

			$key = $issue['code'];

			if (!isset($minus[$issue['block']][$key])) {
				$minus[$issue['block']][$key] = array('level' => $issue['level'], 'n' => 0);
			}

			$minus[$issue['block']][$key]['n']++;

			if ($weights[$issue['level']] > $weights[$minus[$issue['block']][$key]['level']]) {
				$minus[$issue['block']][$key]['level'] = $issue['level'];
			}
		}

		$scores = array();

		foreach ($minus as $block => $codes) {
			$total = 0;

			foreach ($codes as $item) {
				// Повторы одной проверки уменьшают оценку меньше, чем разные проблемы
				$w = $weights[$item['level']];
				$total += min($caps[$item['level']], $w + ($item['n'] - 1) * $w / 4);
			}

			$scores[$block] = max(0, (int)round(100 - $total));
		}

		$parts = array('security' => 0.5, 'seo' => 0.3, 'lang' => 0.2);
		$sum = 0;
		$wsum = 0;

		foreach ($parts as $block => $w) {
			if (!empty($applicable[$block])) {
				$sum += $scores[$block] * $w;
				$wsum += $w;
			} else {
				$scores[$block] = null;
			}
		}

		$scores['total'] = $wsum ? (int)round($sum / $wsum) : 0;

		// Критическая проблема безопасности не даёт общей оценке быть выше 49
		foreach ($issues as $issue) {
			if ($issue['block'] === 'security' && $issue['level'] === 'critical') {
				$scores['total'] = min($scores['total'], 49);
				break;
			}
		}

		return $scores;
	}
}
