<?php
/**
 * KRIATIZ Аудит — SEO и «Мова і закон».
 */
class KriatizAuditSeo {

	public static function languages($s) {
		static $list = null;

		if ($list === null) {
			$list = array();

			foreach ($s->db->query("SELECT language_id, code, name FROM `" . DB_PREFIX . "language` WHERE status = '1' ORDER BY sort_order")->rows as $row) {
				$list[(int)$row['language_id']] = $row;
			}
		}

		return $list;
	}

	public static function mainLanguageId($s) {
		$code = $s->config->get('config_language');

		foreach (self::languages($s) as $id => $lang) {
			if ($lang['code'] === $code) {
				return $id;
			}
		}

		$ids = array_keys(self::languages($s));

		return $ids ? $ids[0] : 1;
	}

	public static function isUkrainian($code) {
		$code = strtolower((string)$code);

		return strpos($code, 'uk') === 0 || strpos($code, 'ua') === 0;
	}

	/* ------------------------------------------------------------------ */
	/* SEO                                                                 */
	/* ------------------------------------------------------------------ */

	public static function seo($s, &$c) {
		$db = $s->db;
		$main = self::mainLanguageId($s);
		$langs = self::languages($s);
		$store = (int)$s->config->get('config_store_id');

		if (!$s->config->get('config_seo_url')) {
			$s->issue('seo', 'seo_url_off', 'warning');
		}

		// SEO URL
		$entities = array('product' => 'product_id', 'category' => 'category_id', 'manufacturer' => 'manufacturer_id', 'information' => 'information_id');
		$missing = array();

		foreach ($entities as $table => $key) {
			$table = KriatizAuditScanner::ident($table);
			$key = KriatizAuditScanner::ident($key);

			if (!$s->tableExists($table)) {
				continue;
			}

			$where = in_array('status', $s->columns($table)) ? " WHERE e.status = '1'" : '';

			if (KriatizAuditScanner::legacy()) {
				$n = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` e LEFT JOIN `" . DB_PREFIX . "url_alias` ua ON (ua.`query` = CONCAT('" . $key . "=', e." . $key . "))" . ($where ? $where . " AND" : " WHERE") . " ua.url_alias_id IS NULL")->row['n'];

				if ($n) {
					$missing[$table] = $n;
				}
			} else {
				foreach ($langs as $id => $lang) {
					$n = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` e LEFT JOIN `" . DB_PREFIX . "seo_url` su ON (su.`query` = CONCAT('" . $key . "=', e." . $key . ") AND su.language_id = '" . (int)$id . "' AND su.store_id = '" . (int)$store . "')" . ($where ? $where . " AND" : " WHERE") . " su.seo_url_id IS NULL")->row['n'];

					if ($n) {
						$missing[$table . ':' . $lang['code']] = $n;
					}
				}
			}
		}

		if ($missing) {
			$s->issue('seo', 'seo_url_missing', array_sum($missing) > 20 ? 'warning' : 'info', 'seo_url', array('counts' => $missing));
		}

		$alias = KriatizAuditScanner::legacy() ? 'url_alias' : 'seo_url';

		if ($s->tableExists($alias)) {
			$cyr = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $alias . "` WHERE keyword <> '' AND CAST(keyword AS BINARY) NOT REGEXP '^[a-z0-9_.\\-]+$'")->row['n'];

			if ($cyr) {
				$examples = array();

				foreach ($db->query("SELECT keyword FROM `" . DB_PREFIX . $alias . "` WHERE keyword <> '' AND CAST(keyword AS BINARY) NOT REGEXP '^[a-z0-9_.\\-]+$' LIMIT 5")->rows as $row) {
					$examples[] = $row['keyword'];
				}

				$s->issue('seo', 'seo_url_bad_chars', 'warning', $alias, array('count' => $cyr, 'list' => $examples));
			}

			$group = KriatizAuditScanner::legacy() ? 'keyword' : 'keyword, store_id';
			$dups = $db->query("SELECT keyword, COUNT(DISTINCT `query`) AS n FROM `" . DB_PREFIX . $alias . "` WHERE keyword <> '' GROUP BY " . $group . " HAVING n > 1 LIMIT 20")->rows;

			if ($dups) {
				$list = array();

				foreach ($dups as $row) {
					$list[] = $row['keyword'] . ' (' . $row['n'] . ')';
				}

				$s->issue('seo', 'seo_url_duplicate', 'warning', $alias, array('count' => count($dups), 'list' => $list));
			}
		}

		// Мета-теги и описания (основной язык)
		$checks = array(
			array('product_description', 'product_id', 'product'),
			array('category_description', 'category_id', 'category'),
		);

		foreach ($checks as $check) {
			list($table, $key, $entity) = array_map(array('KriatizAuditScanner', 'ident'), $check);

			if (!$s->tableExists($table)) {
				continue;
			}

			$cols = $s->columns($table);
			$active = $s->tableExists($entity) && in_array('status', $s->columns($entity)) ? " AND d." . $key . " IN (SELECT " . $key . " FROM `" . DB_PREFIX . $entity . "` WHERE status = '1')" : '';

			if (in_array('meta_title', $cols)) {
				$n = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` d WHERE d.language_id = '" . (int)$main . "' AND TRIM(d.meta_title) = ''" . $active)->row['n'];

				if ($n) {
					$s->issue('seo', 'meta_title_empty', $n > 10 ? 'warning' : 'info', $entity, array('count' => $n));
				}

				$dups = (int)$db->query("SELECT COUNT(*) AS n FROM (SELECT d.meta_title FROM `" . DB_PREFIX . $table . "` d WHERE d.language_id = '" . (int)$main . "' AND TRIM(d.meta_title) <> ''" . $active . " GROUP BY d.meta_title HAVING COUNT(*) > 1) t")->row['n'];

				if ($dups) {
					$s->issue('seo', 'meta_title_duplicate', 'info', $entity, array('count' => $dups));
				}
			}

			if (in_array('meta_description', $cols)) {
				$n = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` d WHERE d.language_id = '" . (int)$main . "' AND TRIM(d.meta_description) = ''" . $active)->row['n'];

				if ($n) {
					$s->issue('seo', 'meta_description_empty', 'info', $entity, array('count' => $n));
				}
			}

			if (in_array('description', $cols) && $entity === 'product') {
				$n = (int)$db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` d WHERE d.language_id = '" . (int)$main . "' AND CHAR_LENGTH(TRIM(d.description)) < 40" . $active)->row['n'];

				if ($n) {
					$s->issue('seo', 'description_empty', $n > 10 ? 'warning' : 'info', $entity, array('count' => $n));
				}
			}
		}

		if (trim((string)$s->config->get('config_meta_title')) === '' || trim((string)$s->config->get('config_meta_description')) === '') {
			$s->issue('seo', 'store_meta_empty', 'warning', 'setting');
		}

		$c['progress'] = 1;

		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Мова і закон                                                        */
	/* ------------------------------------------------------------------ */

	public static function lang($s, &$c) {
		$langs = self::languages($s);
		$main = self::mainLanguageId($s);
		$uk = 0;

		foreach ($langs as $id => $lang) {
			if (self::isUkrainian($lang['code'])) {
				$uk = $id;
			}
		}

		$s->state['lang'] = array('count' => count($langs), 'uk' => $uk);

		if (!$uk) {
			$s->issue('lang', 'lang_no_uk', 'warning');
		} elseif ($main !== $uk) {
			$s->issue('lang', 'lang_default_not_uk', 'critical', '', array('default' => isset($langs[$main]) ? $langs[$main]['name'] : ''));
		}

		// Витрина: украинский языковой пакет на самом деле русский
		if ($uk) {
			$file = DIR_CATALOG . 'language/' . basename($langs[$uk]['code']) . '/common/header.php';

			if (is_file($file)) {
				$text = (string)@file_get_contents($file);

				if (preg_match('/(Главная|Корзина|Личный кабинет|Оформление заказа)/u', $text)) {
					$s->issue('lang', 'lang_pack_russian', 'warning', 'catalog/language/' . $langs[$uk]['code']);
				}
			}
		}

		// Непереведённые товары и категории
		if ($uk && count($langs) > 1) {
			foreach (array('product' => 'product_id', 'category' => 'category_id') as $entity => $key) {
				$key = KriatizAuditScanner::ident($key);
				$table = KriatizAuditScanner::ident($entity . '_description');

				if (!$s->tableExists($table)) {
					continue;
				}

				$total = (int)$s->db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` WHERE language_id = '" . (int)$uk . "'")->row['n'];

				if (!$total) {
					continue;
				}

				$same = 0;

				foreach ($langs as $id => $lang) {
					if ($id === $uk) {
						continue;
					}

					// Совпадает с другим языком и содержит русские буквы — значит, не переведено
					$n = (int)$s->db->query("SELECT COUNT(*) AS n FROM `" . DB_PREFIX . $table . "` u INNER JOIN `" . DB_PREFIX . $table . "` o ON (o." . $key . " = u." . $key . " AND o.language_id = '" . (int)$id . "') WHERE u.language_id = '" . (int)$uk . "' AND (TRIM(u.name) = '' OR (u.name = o.name AND u.name REGEXP '[ыэъёЫЭЪЁ]'))")->row['n'];
					$same = max($same, $n);
				}

				$percent = round($same / $total * 100);

				if ($percent >= 5) {
					$s->issue('lang', 'lang_untranslated', $percent >= 30 ? 'warning' : 'info', $entity, array('count' => $same, 'total' => $total, 'percent' => $percent));
				}
			}
		}

		// Несколько языков: видит ли Google каждую языковую версию
		if (count($langs) > 1) {
			$front = isset($s->state['front']) ? $s->state['front'] : array();

			if (!empty($front['code']) && empty($front['hreflang'])) {
				$s->issue('lang', 'lang_no_hreflang', 'warning', 'home', array('count' => count($langs)));
			}

			$module = (bool)$s->config->get('module_kriatiz_multilang_status') || (bool)$s->config->get('kriatiz_multilang_status');

			if (!$module) {
				$seolang = $s->db->query("SELECT 1 FROM `" . DB_PREFIX . "setting` WHERE `key` LIKE 'asc_langmark%' LIMIT 1")->num_rows;

				if (!$seolang && empty($front['hreflang'])) {
					$s->issue('lang', 'lang_one_url', 'warning', '', array('count' => count($langs)));
				}
			}
		}

		$c['progress'] = 1;

		return true;
	}
}
