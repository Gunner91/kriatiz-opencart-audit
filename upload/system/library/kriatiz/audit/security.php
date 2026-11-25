<?php
/**
 * KRIATIZ Аудит — проверки безопасности.
 */
class KriatizAuditSecurity {
	const MAX_FILE = 3145728;
	const CODE_EXT = '/\.(php|phtml|php[3-8]|pht|phar|inc|js|twig|tpl|html?|htaccess)$/i';
	const PHP_EXT = '/\.(php|phtml|php[3-8]|pht|phar|inc)$/i';
	const IMAGE_EXT = '/\.(jpe?g|png|gif|webp|svg|ico|bmp|avif)$/i';

	/* ------------------------------------------------------------------ */
	/* Фаза 1. Настройки сервера и магазина                               */
	/* ------------------------------------------------------------------ */

	public static function config($s, &$c) {
		$root = $s->root;

		// Установщик OpenCart: папка install или переименованная копия (install_old, _install_done…)
		foreach ((array)@scandir($root) as $dir) {
			if ($dir === '.' || $dir === '..' || !is_dir($root . $dir)) {
				continue;
			}

			if (is_file($root . $dir . '/controller/install/step_1.php') && is_file($root . $dir . '/index.php')) {
				$s->issue('security', 'install_dir', 'critical', $dir . '/');
			}
		}

		if (is_writable($root . 'config.php') || is_writable(DIR_APPLICATION . 'config.php')) {
			$s->issue('security', 'config_writable', 'info', 'config.php');
		}

		if (basename(rtrim(DIR_APPLICATION, '/\\')) === 'admin') {
			$s->issue('security', 'admin_default_path', 'info', 'admin/');
		}

		if (version_compare(PHP_VERSION, '7.4.0', '<')) {
			$s->issue('security', 'php_old', 'warning', '', array('version' => PHP_VERSION));
		} elseif (version_compare(PHP_VERSION, '8.2.0', '<')) {
			$s->issue('security', 'php_eol', 'info', '', array('version' => PHP_VERSION));
		}

		if (version_compare(VERSION, '3.0.0.0', '<')) {
			$s->issue('security', 'oc_branch_old', 'warning', '', array('version' => VERSION));
		} elseif (version_compare(VERSION, '3.0.3.6', '<')) {
			$s->issue('security', 'oc_old', 'warning', '', array('version' => VERSION));
		}

		$https = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : '';

		if (stripos($https, 'https://') !== 0 && stripos(HTTP_CATALOG, 'https://') !== 0) {
			$s->issue('security', 'no_https', 'warning');
		}

		// Администраторы
		if ($s->tableExists('user') && $s->tableExists('user_group')) {
			$admins = $s->db->query("SELECT u.username, u.email, u.date_added, ug.permission FROM `" . DB_PREFIX . "user` u LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id) WHERE u.status = '1'")->rows;
			$full = array();
			$recent = array();

			foreach ($admins as $row) {
				if (strtolower($row['username']) === 'admin') {
					$s->issue('security', 'admin_username', 'warning', 'user:admin');
				}

				// Права группы в OpenCart 2.3 и 3.0 хранятся в JSON
				$perm = json_decode((string)$row['permission'], true);
				$can_users = is_array($perm) && isset($perm['modify']) && is_array($perm['modify']) && in_array('user/user', $perm['modify']);

				if ($can_users) {
					$full[] = $row['username'];

					if (strtotime($row['date_added']) > time() - 30 * 86400) {
						$recent[] = $row['username'] . ' (' . substr($row['date_added'], 0, 10) . ')';
					}
				}
			}

			if ($recent) {
				$s->issue('security', 'admin_recent', 'warning', 'user:recent', array('list' => $recent));
			}

			if (count($full) > 3) {
				$s->issue('security', 'admin_many', 'info', 'user:many', array('count' => count($full), 'list' => array_slice($full, 0, 20)));
			}
		}

		$c['progress'] = 1;

		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Фаза 2. Сверка ядра с оригиналом OpenCart / ocStore                */
	/* ------------------------------------------------------------------ */

	public static function coreMap($s) {
		static $map = null;

		if ($map !== null) {
			return $map;
		}

		$map = array();

		if (empty($s->state['core']['name'])) {
			return $map;
		}

		$file = __DIR__ . '/hashes/' . basename($s->state['core']['name']) . '.json.gz';

		if (is_file($file) && function_exists('gzdecode')) {
			$map = json_decode(gzdecode(file_get_contents($file)), true);
		}

		return is_array($map) ? $map : array();
	}

	public static function detectCore($s) {
		$index = json_decode(@file_get_contents(__DIR__ . '/hashes/index.json'), true);

		if (!is_array($index) || !function_exists('gzdecode')) {
			return '';
		}

		$candidates = array();
		$short = implode('.', array_slice(explode('.', VERSION), 0, 2));

		foreach ($index as $name => $info) {
			if ($info['version'] === VERSION) {
				$candidates[$name] = 2;
			} elseif (strpos($info['version'], $short . '.') === 0) {
				$candidates[$name] = 1;
			}
		}

		// Сначала точные совпадения версии, затем той же ветки
		arsort($candidates);
		$best = '';
		$best_score = -1;

		foreach (array_keys($candidates) as $name) {
			$map = json_decode(gzdecode(file_get_contents(__DIR__ . '/hashes/' . $name . '.json.gz')), true);

			if (!is_array($map)) {
				continue;
			}

			$sample = array_slice($map, 0, 400, true);
			$hits = 0;

			foreach ($sample as $path => $hash) {
				$file = $s->root . $path;

				if (is_file($file) && substr(sha1_file($file), 0, 16) === $hash) {
					$hits++;
				}
			}

			$score = $hits / max(1, count($sample)) + ($candidates[$name] === 2 ? 0.05 : 0);

			if ($score > $best_score) {
				$best_score = $score;
				$best = $name;
			}
		}

		return $best_score > 0.3 ? $best : '';
	}

	public static function core($s, &$c, $start) {
		if (!isset($c['offset'])) {
			$name = self::detectCore($s);
			$s->state['core'] = array('name' => $name, 'modified' => array(), 'missing' => 0);
			$c['offset'] = 0;

			if (!$name) {
				$s->issue('security', 'core_unknown', 'info', '', array('version' => VERSION));
				$c['progress'] = 1;

				return true;
			}
		}

		$map = self::coreMap($s);
		$paths = array_keys($map);
		$total = count($paths);

		while ($c['offset'] < $total && $s->timeLeft($start)) {
			$path = $paths[$c['offset']++];
			$file = $s->root . $path;

			// Админка может быть переименована
			if (strpos($path, 'admin/') === 0 && !is_file($file)) {
				$file = rtrim(str_replace('\\', '/', DIR_APPLICATION), '/') . '/' . substr($path, 6);
			}

			if (!is_file($file)) {
				$s->state['core']['missing']++;
				continue;
			}

			if (substr(sha1_file($file), 0, 16) === $map[$path]) {
				$s->state['clean'][$path] = 1;
				continue;
			}

			$content = @file_get_contents($file, false, null, 0, self::MAX_FILE);
			$group = preg_match(self::PHP_EXT, $path) ? 'php' : (basename($path) === '.htaccess' ? 'htaccess' : 'js');
			$hit = $s->match($group, (string)$content);

			if ($hit && $hit['level'] === 'critical') {
				$s->issue('security', 'core_infected', 'critical', $path, $hit);
			} else {
				if ($hit) {
					$s->issue('security', 'suspicious_code', $hit['level'], $path, $hit);
				}

				if (count($s->state['core']['modified']) < 200) {
					$s->state['core']['modified'][] = $path;
				}
			}

			// Файл уже проверен — фаза «Усі файли» его пропустит
			$s->state['clean'][$path] = 1;

			$s->stat('core_checked');
		}

		$c['progress'] = $total ? $c['offset'] / $total : 1;

		if ($c['offset'] >= $total) {
			$modified = $s->state['core']['modified'];

			if ($modified) {
				$s->issue('security', 'core_modified', count($modified) > 30 ? 'warning' : 'info', 'core', array('count' => count($modified), 'list' => array_slice($modified, 0, 60), 'base' => $s->state['core']['name']));
			}

			$s->state['core']['modified'] = array();

			return true;
		}

		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Фаза 3. Все файлы сайта                                             */
	/* ------------------------------------------------------------------ */

	private static function skipDir($rel) {
		return (bool)preg_match('#^(\.git|node_modules|system/storage/(cache|session|logs)|vendor/bin)(/|$)#', $rel)
			|| (bool)preg_match('#(^|/)(\.svn|\.idea|\.vscode)(/|$)#', $rel);
	}

	public static function files($s, &$c, $start) {
		if (!isset($c['dirs'])) {
			$c['dirs'] = array('');
			$c['queue'] = array();
			$c['done_dirs'] = 0;
			$c['flagged'] = 0;
		}

		$map = self::coreMap($s);

		while ($s->timeLeft($start)) {
			if (!$c['queue']) {
				if (!$c['dirs']) {
					$c['progress'] = 1;
					unset($s->state['clean']);

					return true;
				}

				$dir = array_pop($c['dirs']);
				$c['done_dirs']++;
				$list = @scandir($s->root . $dir);

				if (!$list) {
					continue;
				}

				foreach ($list as $name) {
					if ($name === '.' || $name === '..') {
						continue;
					}

					$rel = ($dir === '' ? '' : $dir . '/') . $name;

					if (is_dir($s->root . $rel)) {
						if (!is_link($s->root . $rel) && !self::skipDir($rel) && count($c['dirs']) < 20000) {
							$c['dirs'][] = $rel;
						}
					} else {
						$c['queue'][] = $rel;
					}
				}

				continue;
			}

			$rel = array_shift($c['queue']);
			self::scanFile($s, $c, $rel, $map);
			$s->stat('files');
		}

		$c['progress'] = min(0.95, $c['done_dirs'] / max(1, $c['done_dirs'] + count($c['dirs'])));

		return false;
	}

	private static function scanFile($s, &$c, $rel, $map) {
		$file = $s->root . $rel;
		$name = basename($rel);
		$in_images = (bool)preg_match('#^image/#', $rel);
		$in_uploads = (bool)preg_match('#^system/storage/(upload|download)/#', $rel);
		$is_php = (bool)preg_match(self::PHP_EXT, $name);

		// PHP там, где его быть не должно
		if ($is_php && ($in_images || $in_uploads)) {
			$s->issue('security', 'php_in_uploads', 'critical', $rel);

			return;
		}

		if ($is_php && $name[0] === '.') {
			$s->issue('security', 'hidden_php', 'warning', $rel);
		}

		// Двойное расширение: shell.php.jpg
		if (preg_match('/\.(php[0-9]?|phtml|phar)\.(jpe?g|png|gif|webp|svg|ico|bmp|txt|pdf|zip)$/i', $name)) {
			$s->issue('security', 'double_extension', 'critical', $rel);

			return;
		}

		// Код, замаскированный под картинку
		if (preg_match(self::IMAGE_EXT, $name)) {
			if ($in_images || $in_uploads) {
				$head = (string)@file_get_contents($file, false, null, 0, 512);

				if (strpos($head, '<?php') !== false || strpos($head, '<?=') !== false) {
					$s->issue('security', 'php_disguised', 'critical', $rel);
				} elseif (preg_match('/\.svg$/i', $name) && preg_match('/<script/i', (string)@file_get_contents($file, false, null, 0, 65536))) {
					$s->issue('security', 'svg_script', 'warning', $rel);
				}
			}

			return;
		}

		// Уже сверено с оригиналом на фазе «Ядро» — не читаем повторно
		if (isset($s->state['clean'][$rel])) {
			return;
		}

		if (!preg_match(self::CODE_EXT, $name) && $name !== '.user.ini') {
			// Подозрительные файлы в корне: дампы, архивы, служебные утилиты
			if (strpos($rel, '/') === false && preg_match('/\.(sql|sql\.gz|tar|tgz|tar\.gz|zip|rar|7z|bak|old)$/i', $name)) {
				$s->state['root_backups'][] = $rel;
			}

			return;
		}

		if (strpos($rel, '/') === false && preg_match('/^(adminer|phpminiadmin|sql|dumper|phpmyadmin)[^\/]*\.php$/i', $name)) {
			$s->issue('security', 'db_tool', 'critical', $rel);
		}

		$size = @filesize($file);

		if ($size === false || $size > self::MAX_FILE) {
			return;
		}

		$content = (string)@file_get_contents($file);

		// Оригинальный файл ядра — пропускаем
		if (isset($map[$rel]) && substr(sha1($content), 0, 16) === $map[$rel]) {
			return;
		}

		if (strpos($rel, '/') === false && $is_php && preg_match('/\bphpinfo\s*\(\s*\)/i', $content) && strlen($content) < 2000) {
			$s->issue('security', 'phpinfo_file', 'warning', $rel);
		}

		if ($name === '.htaccess' || $name === '.user.ini') {
			$group = 'htaccess';
		} elseif ($is_php) {
			$group = 'php';
		} elseif (preg_match('/\.(twig|tpl|html?)$/i', $name)) {
			$group = 'html';
		} else {
			$group = 'js';
		}

		$hit = $group === 'html' ? $s->matchHtml($content) : $s->match($group, $content);

		// В PHP-файлах тоже ищем вставленный JS (скиммеры в шаблонах и контроллерах)
		if (!$hit && $group === 'php') {
			$hit = $s->match('js', $content);
		}

		if ($hit && $c['flagged'] < 400) {
			$code = $hit['id'] === 'nulled' ? 'nulled_module' : ($group === 'htaccess' ? 'htaccess_' . ($hit['level'] === 'critical' ? 'malicious' : 'suspicious') : ($hit['level'] === 'critical' ? 'malware' : 'suspicious_code'));
			$s->issue('security', $code, $hit['level'], $rel, $hit);
			$c['flagged']++;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Фаза 4. База данных                                                 */
	/* ------------------------------------------------------------------ */

	public static function dbTargets($s) {
		$targets = array();
		$add = function ($table, $key, $fields, $where = '') use ($s, &$targets) {
			if (!$s->tableExists($table)) {
				return;
			}

			$cols = $s->columns($table);
			$fields = array_values(array_intersect($fields, $cols));

			if ($fields && in_array($key, $cols)) {
				$targets[] = array('table' => $table, 'key' => $key, 'fields' => $fields, 'where' => $where, 'lang' => in_array('language_id', $cols));
			}
		};

		$add('product_description', 'product_id', array('description', 'tag', 'meta_title', 'meta_description', 'meta_h1'));
		$add('category_description', 'category_id', array('description', 'meta_title', 'meta_description'));
		$add('information_description', 'information_id', array('description', 'meta_title'));
		$add('manufacturer_description', 'manufacturer_id', array('description'));
		$add('article_description', 'article_id', array('description'));
		$add('blog_category_description', 'blog_category_id', array('description'));
		$add('banner_image', 'banner_image_id', array('link', 'title'));
		$add('module', 'module_id', array('setting'));
		$add('setting', 'setting_id', array('value'));
		$add('modification', 'modification_id', array('xml'));
		$add('review', 'review_id', array('text'));

		return $targets;
	}

	public static function database($s, &$c, $start) {
		if (!isset($c['t'])) {
			$c['t'] = 0;
			$c['offset'] = 0;
			$c['flagged'] = 0;
			$c['targets'] = self::dbTargets($s);
		}

		$targets = $c['targets'];
		$spam = $s->spamWords();

		while ($c['t'] < count($targets) && $s->timeLeft($start)) {
			$t = $targets[$c['t']];
			$t['table'] = KriatizAuditScanner::ident($t['table']);
			$t['key'] = KriatizAuditScanner::ident($t['key']);
			$t['fields'] = array_map(array('KriatizAuditScanner', 'ident'), $t['fields']);
			$select = '`' . $t['key'] . '`, `' . implode('`, `', $t['fields']) . '`' . ($t['lang'] ? ', language_id' : '') . ($t['table'] === 'modification' ? ', name' : '') . ($t['table'] === 'setting' ? ', `key`' : '');
			$rows = $s->db->query("SELECT " . $select . " FROM `" . DB_PREFIX . $t['table'] . "` ORDER BY `" . $t['key'] . "` LIMIT " . (int)$c['offset'] . ", 200")->rows;

			if (!$rows) {
				$c['t']++;
				$c['offset'] = 0;
				continue;
			}

			$c['offset'] += 200;
			$s->stat('rows', count($rows));

			foreach ($rows as $row) {
				foreach ($t['fields'] as $field) {
					$value = (string)$row[$field];

					if ($value === '' || strlen($value) < 8) {
						continue;
					}

					// Одна запись на нескольких языках — одна находка
					$target = $t['table'] . ':' . $row[$t['key']] . ($t['table'] === 'setting' ? ':' . $row['key'] : '') . ($t['table'] === 'modification' ? ':' . $row['name'] : '') . ':' . $field;

					if (isset($c['seen'][$target])) {
						continue;
					}

					$before = count($s->issues);
					self::analyzeHtml($s, $c, $t['table'], $target, $value, $spam);

					if (count($s->issues) > $before) {
						$c['seen'][$target] = 1;
					}
				}
			}
		}

		$c['progress'] = $targets ? $c['t'] / count($targets) : 1;

		return $c['t'] >= count($targets);
	}

	private static function analyzeHtml($s, &$c, $table, $target, $value, $spam) {
		if ($c['flagged'] > 300) {
			return;
		}

		$html = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		// Модификаторы: исполняемый PHP внутри XML — типичный бэкдор
		if ($table === 'modification') {
			$hit = $s->match('php', $html);

			if ($hit) {
				$s->issue('security', 'mod_malicious', $hit['level'], $target, $hit);
				$c['flagged']++;
			}

			return;
		}

		if (stripos($html, '<') === false && stripos($html, 'http') === false && stripos($html, 'script') === false) {
			return;
		}

		$hit = $s->matchHtml($html);

		if ($hit) {
			$s->issue('security', $hit['level'] === 'critical' ? 'db_malicious' : 'db_suspicious', $hit['level'], $target, $hit);
			$c['flagged']++;

			return;
		}

		// Внешние скрипты с неизвестных доменов
		if (preg_match_all('/<script[^>]+src\s*=\s*["\']?(?:https?:)?\/\/([^\/"\'\s>]+)/i', $html, $m)) {
			$unknown = array();

			foreach (array_unique($m[1]) as $host) {
				$host = strtolower(preg_replace('/:\d+$/', '', $host));

				if (!$s->knownDomain($host)) {
					$unknown[] = $host;
				}
			}

			if ($unknown) {
				$s->issue('security', 'db_external_script', $table === 'setting' ? 'warning' : 'critical', $target, array('hosts' => $unknown));
				$c['flagged']++;

				return;
			}
		}

		// Скрытые ссылки на чужие сайты
		if (preg_match('/<(div|span|p)[^>]+style\s*=\s*["\'][^"\']*(display\s*:\s*none|visibility\s*:\s*hidden|font-size\s*:\s*[01]px|left\s*:\s*-\d{3,})[^>]*>[\s\S]{0,3000}?<a\s[^>]*href\s*=\s*["\']https?:\/\/([^\/"\']+)/i', $html, $hm) && !$s->knownDomain($hm[3])) {
			$s->issue('security', 'db_hidden_links', 'critical', $target, array('hosts' => array($hm[3])));
			$c['flagged']++;

			return;
		}

		// Спам-ссылки
		if (preg_match('/<a\s[^>]*href\s*=\s*["\']https?:\/\/([^\/"\']+)[^>]*>[^<]{0,200}/i', $html) && preg_match('/(' . $spam . ')/iu', strip_tags($html) . ' ' . $html, $sm)) {
			if (preg_match('/href\s*=\s*["\'][^"\']*(' . $spam . ')/iu', $html)) {
				$s->issue('security', 'db_spam_links', 'warning', $target, array('word' => $sm[1]));
				$c['flagged']++;
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Фаза 5. Витрина глазами посетителя, Googlebot и смартфона           */
	/* ------------------------------------------------------------------ */

	public static function frontend($s, &$c, $start) {
		if (!isset($c['i'])) {
			$c['i'] = 0;
		}

		$steps = array('home', 'bot', 'mobile', 'product', 'checkout', 'exposed', 'listing', 'notfound', 'robots', 'https');

		while ($c['i'] < count($steps) && $s->timeLeft($start)) {
			$method = 'front' . ucfirst($steps[$c['i']]);
			self::$method($s, $c);
			$c['i']++;
		}

		$c['progress'] = $c['i'] / count($steps);

		return $c['i'] >= count($steps);
	}

	private static function host($url) {
		return strtolower((string)parse_url($url, PHP_URL_HOST));
	}

	private static function visibleText($html) {
		$html = preg_replace('/<(script|style|noscript)[\s\S]*?<\/\1>/i', ' ', $html);

		return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
	}

	private static function externalLinks($s, $html) {
		$hosts = array();

		if (preg_match_all('/<a\s[^>]*href\s*=\s*["\']https?:\/\/([^\/"\':]+)/i', $html, $m)) {
			foreach ($m[1] as $h) {
				$h = strtolower($h);

				if (!$s->knownDomain($h)) {
					$hosts[$h] = true;
				}
			}
		}

		return array_keys($hosts);
	}

	private static function scriptHosts($s, $html) {
		$hosts = array();

		if (preg_match_all('/<script[^>]+src\s*=\s*["\']?(?:https?:)?\/\/([^\/"\'\s>:]+)/i', $html, $m)) {
			foreach ($m[1] as $h) {
				$hosts[strtolower($h)] = true;
			}
		}

		return array_keys($hosts);
	}

	private static function frontHome($s, &$c) {
		$url = $s->catalogUrl();
		$r = $s->fetch($url, 'browser');
		$s->state['front'] = array('code' => $r['code'], 'error' => $r['error'], 'hreflang' => 0, 'lang' => '', 'links' => array(), 'spam' => array(), 'size' => strlen($r['body']), 'location' => $r['location']);

		if ($r['error'] || !$r['code']) {
			$s->issue('security', 'front_unreachable', 'info', $url, array('error' => $r['error']));

			return;
		}

		$body = $r['body'];

		if (preg_match('/(<b>)?\s*(Notice|Warning|Deprecated|Fatal error|Parse error|Unknown)\s*(<\/b>)?\s*:\s[^<\n]{0,200}\s+in\s+(<b>)?[^<\n]*\.php/i', $body, $m)) {
			$s->issue('security', 'php_errors_visible', 'critical', $url, array('snippet' => KriatizAuditScanner::snippet(strip_tags($m[0]), 0, 200)));
		}

		$hit = $s->matchHtml($body);

		if ($hit) {
			$s->issue('security', $hit['level'] === 'critical' ? 'front_malicious' : 'front_suspicious', $hit['level'], $url, $hit);
		}

		$unknown = array();

		foreach (self::scriptHosts($s, $body) as $h) {
			if (!$s->knownDomain($h)) {
				$unknown[] = $h;
			}
		}

		if ($unknown) {
			$s->issue('security', 'front_unknown_scripts', 'warning', 'home', array('hosts' => $unknown));
		}

		$text = self::visibleText($body);

		if (preg_match_all('/\b(' . $s->spamWords() . ')\b/iu', $text, $sm)) {
			$s->state['front']['spam'] = array_values(array_unique(array_map(array('KriatizAuditScanner', 'lower'), $sm[1])));
		}

		$s->state['front']['links'] = self::externalLinks($s, $body);
		$s->state['front']['hreflang'] = preg_match_all('/<link[^>]+hreflang\s*=/i', $body);

		if (preg_match('/<html[^>]+lang\s*=\s*["\']([^"\']+)/i', $body, $lm)) {
			$s->state['front']['lang'] = strtolower($lm[1]);
		}

		$missing = array();

		foreach (array('x-frame-options', 'x-content-type-options') as $h) {
			if (!isset($r['headers'][$h])) {
				$missing[] = $h;
			}
		}

		if ($missing) {
			$s->issue('security', 'security_headers', 'info', 'home', array('list' => $missing));
		}
	}

	private static function frontBot($s, &$c) {
		$front = isset($s->state['front']) ? $s->state['front'] : array();

		if (empty($front['code'])) {
			return;
		}

		$url = $s->catalogUrl();
		$r = $s->fetch($url, 'bot');
		$own = self::host($url);

		if ($r['location'] && self::host($r['location']) && self::host($r['location']) !== $own && self::host($front['location']) !== self::host($r['location'])) {
			$s->issue('security', 'cloaking_redirect', 'critical', $url, array('location' => $r['location'], 'agent' => 'Googlebot'));

			return;
		}

		$body = $r['body'];
		$spam = array();

		if (preg_match_all('/\b(' . $s->spamWords() . ')\b/iu', self::visibleText($body), $sm)) {
			$spam = array_values(array_unique(array_map(array('KriatizAuditScanner', 'lower'), $sm[1])));
		}

		$new_spam = array_diff($spam, $front['spam']);
		$new_links = array_diff(self::externalLinks($s, $body), $front['links']);

		if ($new_spam || count($new_links) >= 3) {
			$s->issue('security', 'cloaking', 'critical', $url, array('words' => array_values($new_spam), 'hosts' => array_slice(array_values($new_links), 0, 20)));
		} elseif ($front['spam']) {
			$s->issue('security', 'spam_on_page', 'warning', $url, array('words' => $front['spam']));
		}
	}

	private static function frontMobile($s, &$c) {
		$url = $s->catalogUrl();
		$r = $s->fetch($url, 'mobile');
		$own = self::host($url);

		if ($r['location'] && self::host($r['location']) && self::host($r['location']) !== $own && !preg_match('/^m\./', self::host($r['location']))) {
			$s->issue('security', 'mobile_redirect', 'critical', $url, array('location' => $r['location']));
		}
	}

	private static function frontProduct($s, &$c) {
		if (!$s->tableExists('product')) {
			return;
		}

		$row = $s->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE status = '1' ORDER BY product_id DESC LIMIT 1")->row;

		if (!$row) {
			return;
		}

		$url = $s->catalogUrl() . 'index.php?route=product/product&product_id=' . (int)$row['product_id'];
		$a = $s->fetch($url, 'browser', true);
		$b = $s->fetch($url, 'bot', true);

		if (!$a['body'] || !$b['body']) {
			return;
		}

		$hit = $s->matchHtml($a['body']);

		if ($hit) {
			$s->issue('security', $hit['level'] === 'critical' ? 'front_malicious' : 'front_suspicious', $hit['level'], 'product:' . (int)$row['product_id'], $hit);
		}

		$wa = array();
		$wb = array();

		if (preg_match_all('/\b(' . $s->spamWords() . ')\b/iu', self::visibleText($a['body']), $m)) {
			$wa = array_unique(array_map(array('KriatizAuditScanner', 'lower'), $m[1]));
		}

		if (preg_match_all('/\b(' . $s->spamWords() . ')\b/iu', self::visibleText($b['body']), $m)) {
			$wb = array_unique(array_map(array('KriatizAuditScanner', 'lower'), $m[1]));
		}

		$new = array_diff($wb, $wa);
		$links = array_diff(self::externalLinks($s, $b['body']), self::externalLinks($s, $a['body']));

		if ($new || count($links) >= 3) {
			$s->issue('security', 'cloaking', 'critical', 'product:' . (int)$row['product_id'], array('words' => array_values($new), 'hosts' => array_slice(array_values($links), 0, 20)));
		}
	}

	private static function frontCheckout($s, &$c) {
		$url = $s->catalogUrl() . 'index.php?route=checkout/checkout';
		$r = $s->fetch($url, 'browser', true);
		$body = $r['body'];

		if (!$body) {
			return;
		}

		$hit = $s->matchHtml($body);

		if ($hit) {
			$s->issue('security', 'checkout_malicious', $hit['level'] === 'critical' ? 'critical' : 'warning', 'checkout', $hit);
		}

		$unknown = array();

		foreach (self::scriptHosts($s, $body) as $h) {
			if (!$s->knownDomain($h)) {
				$unknown[] = $h;
			}
		}

		if ($unknown) {
			$s->issue('security', 'checkout_unknown_scripts', 'warning', 'checkout', array('hosts' => $unknown));
		}

		if (preg_match_all('/<form[^>]+action\s*=\s*["\']https?:\/\/([^\/"\':]+)/i', $body, $m)) {
			foreach (array_unique($m[1]) as $h) {
				if (!$s->knownDomain($h)) {
					$s->issue('security', 'form_external', 'critical', 'checkout', array('hosts' => array($h)));
				}
			}
		}
	}

	private static function frontExposed($s, &$c) {
		$base = $s->catalogUrl();
		$checks = array(
			'system/storage/logs/error.log' => 'exposed_logs',
			'.git/HEAD'                      => 'exposed_git',
			'.env'                           => 'exposed_env',
		);

		foreach ($checks as $path => $code) {
			if (!file_exists($s->root . $path)) {
				continue;
			}

			$r = $s->fetch($base . $path, 'browser');

			if ($r['code'] == 200 && strlen(trim($r['body'])) > 0 && stripos($r['body'], '<html') === false) {
				$s->issue('security', $code, 'critical', $path);
			}
		}

		if (!empty($s->state['root_backups'])) {
			$open = array();

			foreach (array_slice($s->state['root_backups'], 0, 10) as $file) {
				$r = $s->fetch($base . rawurlencode($file), 'browser');

				if ($r['code'] == 200 && strlen($r['body']) > 100 && stripos(substr($r['body'], 0, 300), '<html') === false) {
					$open[] = $file;
				}
			}

			if ($open) {
				$s->issue('security', 'exposed_backup', 'critical', 'root', array('list' => $open));
			} else {
				$s->issue('security', 'backup_in_root', 'info', 'root', array('list' => array_slice($s->state['root_backups'], 0, 10)));
			}
		}
	}

	private static function frontListing($s, &$c) {
		$r = $s->fetch($s->catalogUrl() . 'image/catalog/', 'browser');

		if ($r['code'] == 200 && preg_match('/<title>\s*Index of/i', $r['body'])) {
			$s->issue('security', 'dir_listing', 'warning', 'image/catalog/');
		}
	}

	private static function frontNotfound($s, &$c) {
		$r = $s->fetch($s->catalogUrl() . 'kriatiz-audit-check-' . substr(md5(uniqid('', true)), 0, 8), 'browser');

		if ($r['code'] == 200) {
			$s->issue('seo', 'no_404', 'warning');
		}
	}

	private static function frontRobots($s, &$c) {
		$base = $s->catalogUrl();
		$r = $s->fetch($base . 'robots.txt', 'browser');
		$sitemap = false;

		if ($r['code'] != 200 || stripos($r['body'], 'user-agent') === false) {
			$s->issue('seo', 'no_robots', 'warning');
		} else {
			if (preg_match('/User-agent:\s*\*\s*[\r\n]+(?:(?!User-agent)[^\n]*\n)*?\s*Disallow:\s*\/\s*$/mi', $r['body'])) {
				$s->issue('seo', 'robots_block_all', 'critical');
			}

			$sitemap = (bool)preg_match('/^\s*Sitemap:\s*\S+/mi', $r['body']);
		}

		if (!$sitemap) {
			foreach (array('sitemap.xml', 'kml-sitemap.xml', 'index.php?route=extension/feed/google_sitemap') as $path) {
				$x = $s->fetch($base . $path, 'browser', true);

				if ($x['code'] == 200 && (stripos($x['body'], '<urlset') !== false || stripos($x['body'], '<sitemapindex') !== false)) {
					$sitemap = true;
					break;
				}
			}

			$s->issue('seo', $sitemap ? 'sitemap_not_in_robots' : 'no_sitemap', $sitemap ? 'info' : 'warning');
		}
	}

	private static function frontHttps($s, &$c) {
		$https = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : '';

		if (stripos($https, 'https://') !== 0) {
			return;
		}

		$r = $s->fetch(preg_replace('#^https://#i', 'http://', $https), 'browser');

		if ($r['code'] == 200) {
			$s->issue('security', 'no_https_redirect', 'warning');
		}
	}
}

