<?php
/**
 * KRIATIZ Аудит — хранение прогонов, находок и исключений.
 */
class ModelExtensionModuleKriatizAudit extends Model {

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kriatiz_audit_run` (
			`run_id` INT(11) NOT NULL AUTO_INCREMENT,
			`status` VARCHAR(16) NOT NULL DEFAULT 'running',
			`state` MEDIUMTEXT NOT NULL,
			`scores` TEXT NOT NULL,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`date_start` DATETIME NOT NULL,
			`date_end` DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (`run_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kriatiz_audit_issue` (
			`issue_id` INT(11) NOT NULL AUTO_INCREMENT,
			`run_id` INT(11) NOT NULL,
			`block` VARCHAR(16) NOT NULL,
			`code` VARCHAR(48) NOT NULL,
			`level` VARCHAR(16) NOT NULL,
			`target` VARCHAR(500) NOT NULL,
			`data` MEDIUMTEXT NOT NULL,
			`hash` CHAR(32) NOT NULL,
			PRIMARY KEY (`issue_id`),
			KEY `run_id` (`run_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kriatiz_audit_ignore` (
			`hash` CHAR(32) NOT NULL,
			`code` VARCHAR(48) NOT NULL,
			`target` VARCHAR(500) NOT NULL,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`hash`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kriatiz_audit_run`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kriatiz_audit_issue`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kriatiz_audit_ignore`");
	}

	public function isInstalled() {
		return (bool)$this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'kriatiz_audit_run') . "'")->num_rows;
	}

	/* Прогоны */

	public function createRun($user_id) {
		// Старые незаконченные прогоны больше не нужны
		$this->db->query("DELETE i FROM `" . DB_PREFIX . "kriatiz_audit_issue` i INNER JOIN `" . DB_PREFIX . "kriatiz_audit_run` r ON (r.run_id = i.run_id) WHERE r.status = 'running'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "kriatiz_audit_run` WHERE status = 'running'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "kriatiz_audit_run` SET status = 'running', state = '" . $this->db->escape(json_encode(array('phase' => 0, 'cursor' => array(), 'stats' => array()))) . "', scores = '', user_id = '" . (int)$user_id . "', date_start = NOW()");

		return (int)$this->db->getLastId();
	}

	public function getRun($run_id) {
		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kriatiz_audit_run` WHERE run_id = '" . (int)$run_id . "'")->row;

		if ($row) {
			$row['state'] = json_decode($row['state'], true);
			$row['scores'] = $row['scores'] ? json_decode($row['scores'], true) : array();
		}

		return $row;
	}

	public function getLastRun($status = 'done') {
		$row = $this->db->query("SELECT run_id FROM `" . DB_PREFIX . "kriatiz_audit_run` WHERE status = '" . $this->db->escape($status) . "' ORDER BY run_id DESC LIMIT 1")->row;

		return $row ? $this->getRun($row['run_id']) : array();
	}

	public function saveState($run_id, $state) {
		$this->db->query("UPDATE `" . DB_PREFIX . "kriatiz_audit_run` SET state = '" . $this->db->escape(json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "' WHERE run_id = '" . (int)$run_id . "'");
	}

	public function finishRun($run_id, $scores) {
		$this->db->query("UPDATE `" . DB_PREFIX . "kriatiz_audit_run` SET status = 'done', scores = '" . $this->db->escape(json_encode($scores)) . "', date_end = NOW(), state = '{}' WHERE run_id = '" . (int)$run_id . "'");

		// Храним три последних прогона
		$keep = array();

		foreach ($this->db->query("SELECT run_id FROM `" . DB_PREFIX . "kriatiz_audit_run` WHERE status = 'done' ORDER BY run_id DESC LIMIT 3")->rows as $row) {
			$keep[] = (int)$row['run_id'];
		}

		if ($keep) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "kriatiz_audit_issue` WHERE run_id NOT IN (" . implode(',', $keep) . ")");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "kriatiz_audit_run` WHERE status = 'done' AND run_id NOT IN (" . implode(',', $keep) . ")");
		}
	}

	public function updateScores($run_id, $scores) {
		$this->db->query("UPDATE `" . DB_PREFIX . "kriatiz_audit_run` SET scores = '" . $this->db->escape(json_encode($scores)) . "' WHERE run_id = '" . (int)$run_id . "'");
	}

	/* Находки */

	public function addIssues($run_id, $issues) {
		foreach ($issues as $issue) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "kriatiz_audit_issue` SET run_id = '" . (int)$run_id . "', block = '" . $this->db->escape($issue['block']) . "', code = '" . $this->db->escape($issue['code']) . "', level = '" . $this->db->escape($issue['level']) . "', target = '" . $this->db->escape(mb_substr($issue['target'], 0, 500)) . "', data = '" . $this->db->escape(json_encode($issue['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)) . "', hash = '" . $this->db->escape($issue['hash']) . "'");
		}
	}

	public function getIssues($run_id) {
		$ignored = $this->getIgnoredHashes();
		$issues = array();

		foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "kriatiz_audit_issue` WHERE run_id = '" . (int)$run_id . "' ORDER BY issue_id")->rows as $row) {
			$row['data'] = json_decode($row['data'], true);
			$row['ignored'] = isset($ignored[$row['hash']]);
			$issues[] = $row;
		}

		return $issues;
	}

	public function getIssue($issue_id) {
		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kriatiz_audit_issue` WHERE issue_id = '" . (int)$issue_id . "'")->row;

		if ($row) {
			$row['data'] = json_decode($row['data'], true);
		}

		return $row;
	}

	/* Исключения («Це не загроза») */

	public function getIgnoredHashes() {
		$list = array();

		foreach ($this->db->query("SELECT hash FROM `" . DB_PREFIX . "kriatiz_audit_ignore`")->rows as $row) {
			$list[$row['hash']] = true;
		}

		return $list;
	}

	public function getIgnored() {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "kriatiz_audit_ignore` ORDER BY date_added DESC")->rows;
	}

	public function ignore($hash, $code, $target, $user_id) {
		$this->db->query("REPLACE INTO `" . DB_PREFIX . "kriatiz_audit_ignore` SET hash = '" . $this->db->escape($hash) . "', code = '" . $this->db->escape($code) . "', target = '" . $this->db->escape(mb_substr($target, 0, 500)) . "', user_id = '" . (int)$user_id . "', date_added = NOW()");
	}

	public function unignore($hash) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "kriatiz_audit_ignore` WHERE hash = '" . $this->db->escape($hash) . "'");
	}
}
