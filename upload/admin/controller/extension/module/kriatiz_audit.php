<?php
/**
 * KRIATIZ Аудит — перевірка на віруси, злам, SEO і мову.
 * Безкоштовний модуль. Тільки читання: нічого не змінює в магазині.
 */
class ControllerExtensionModuleKriatizAudit extends Controller {
	const ROUTE = 'extension/module/kriatiz_audit';
	const VERSION = '1.0.0';

	// Сторінки модулів KRIATIZ на форумі (посилання лише всередині форуму)
	const URL_MULTILANG = 'https://opencartforum.com/files/file/9789-kriatiz-multilang-seo-%E2%80%94-movni-url-ru-en-hreflang-ukrainska-za-zamovchuvanniam/';
	const URL_AUTHOR = 'https://opencartforum.com/profile/697033-gunner91/';

	// Для демо й користувачів без права змін: не частіше одного запуску за цей час
	const VIEWER_COOLDOWN = 900;

	private function legacy() {
		return version_compare(VERSION, '3.0.0.0', '<');
	}

	private function token() {
		return $this->legacy() ? 'token=' . $this->session->data['token'] : 'user_token=' . $this->session->data['user_token'];
	}

	private function extensionsRoute() {
		return $this->legacy() ? 'extension/extension' : 'marketplace/extension';
	}

	private function link($method = '', $args = '') {
		return str_replace('&amp;', '&', $this->url->link(self::ROUTE . ($method ? '/' . $method : ''), $this->token() . ($args ? '&' . $args : ''), true));
	}

	private function json($data) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
	}

	private function canModify() {
		return $this->user->hasPermission('modify', self::ROUTE);
	}

	private function model() {
		$this->load->model(self::ROUTE);

		return $this->model_extension_module_kriatiz_audit;
	}

	private function render($data) {
		$t = $this->language->all();
		extract($data);
		ob_start();
		include(DIR_TEMPLATE . 'extension/module/kriatiz_audit.tpl');

		return ob_get_clean();
	}

	private function loadScanner() {
		require_once(DIR_SYSTEM . 'library/kriatiz/audit/scanner.php');
	}

	/* ------------------------------------------------------------------ */
	/* Сторінка модуля                                                     */
	/* ------------------------------------------------------------------ */

	public function index() {
		$this->load->language(self::ROUTE);
		$this->document->setTitle($this->language->get('heading_title_plain'));
		$model = $this->model();

		if (!$model->isInstalled()) {
			$model->install();
		}

		$asset = function ($file) {
			$path = DIR_APPLICATION . 'view/javascript/kriatiz_audit/' . $file;

			return 'view/javascript/kriatiz_audit/' . $file . '?v=' . (is_file($path) ? filemtime($path) : self::VERSION);
		};

		$this->document->addStyle($asset('admin.css'));
		$this->document->addScript($asset('admin.js'));

		$last = $model->getLastRun('done');
		$running = $model->getLastRun('running');

		$data = array(
			'version'     => self::VERSION,
			'can_modify'  => $this->canModify(),
			'last'        => $last ? $this->runPayload($last) : null,
			'running_id'  => $running ? (int)$running['run_id'] : 0,
			'urls'        => array(
				'start'    => $this->link('start'),
				'step'     => $this->link('step'),
				'results'  => $this->link('results'),
				'ignore'   => $this->link('ignore'),
				'unignore' => $this->link('unignore'),
				'export'   => $this->link('export'),
				'back'     => str_replace('&amp;', '&', $this->url->link($this->extensionsRoute(), $this->token() . '&type=module', true)),
				'multilang'=> self::URL_MULTILANG,
				'author'   => self::URL_AUTHOR,
			),
			'texts'       => $this->jsTexts(),
			'ignored'     => $model->getIgnored(),
			'breadcrumbs' => array(
				array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $this->token(), true)),
				array('text' => $this->language->get('text_extension'), 'href' => $this->url->link($this->extensionsRoute(), $this->token() . '&type=module', true)),
				array('text' => $this->language->get('heading_title_plain'), 'href' => $this->link()),
			),
		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->render($data));
	}

	/** Тексти перевірок для інтерфейсу: назва, чим небезпечно, як виправити. */
	private function jsTexts() {
		$all = $this->language->all();
		$texts = array();

		foreach ($all as $key => $value) {
			if (strpos($key, 'check_') === 0 || strpos($key, 'js_') === 0 || strpos($key, 'phase_') === 0 || strpos($key, 'level_') === 0 || strpos($key, 'block_') === 0) {
				$texts[$key] = $value;
			}
		}

		return $texts;
	}

	private function runPayload($run) {
		$issues = $this->model()->getIssues($run['run_id']);

		return array(
			'run_id'     => (int)$run['run_id'],
			'date_start' => $run['date_start'],
			'date_end'   => $run['date_end'],
			'scores'     => $run['scores'],
			'issues'     => $issues,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Перевірка                                                           */
	/* ------------------------------------------------------------------ */

	public function start() {
		$this->load->language(self::ROUTE);
		$model = $this->model();

		if (!$model->isInstalled()) {
			$model->install();
		}

		// Без права змін (демо) — не частіше одного разу на 15 хвилин
		if (!$this->canModify()) {
			$last = $model->getLastRun('done');
			$running = $model->getLastRun('running');

			if ($running && strtotime($running['date_start']) > time() - 600) {
				$this->json(array('run_id' => (int)$running['run_id']));

				return;
			}

			if ($last && strtotime($last['date_end']) > time() - self::VIEWER_COOLDOWN) {
				$this->json(array('cooldown' => true, 'message' => $this->language->get('js_cooldown')));

				return;
			}
		}

		$run_id = $model->createRun((int)$this->user->getId());
		$this->json(array('run_id' => $run_id));
	}

	public function step() {
		$this->load->language(self::ROUTE);
		$model = $this->model();
		$run_id = isset($this->request->post['run_id']) ? (int)$this->request->post['run_id'] : 0;
		$run = $model->getRun($run_id);

		if (!$run || $run['status'] !== 'running') {
			$this->json(array('error' => $this->language->get('js_error_run')));

			return;
		}

		@set_time_limit(60);
		$this->loadScanner();

		$scanner = new KriatizAuditScanner($this->registry, is_array($run['state']) ? $run['state'] : array());
		$done = $scanner->step();
		$model->addIssues($run_id, $scanner->issues);
		$model->saveState($run_id, $scanner->state);
		$progress = $scanner->progress();

		if ($done) {
			$scores = $this->computeScores($run_id);
			$scores['stats'] = isset($scanner->state['stats']) ? $scanner->state['stats'] : array();
			$scores['core'] = !empty($scanner->state['core']['name']) ? $scanner->state['core']['name'] : '';
			$model->finishRun($run_id, $scores);
			$this->json(array('done' => true, 'progress' => 100, 'run' => $this->runPayload($model->getRun($run_id))));

			return;
		}

		$this->json(array('done' => false, 'progress' => $progress['percent'], 'phase' => $progress['phase'], 'files' => $progress['files'], 'rows' => $progress['rows'], 'found' => count($scanner->issues)));
	}

	/** Пересчитать оценки, сохранив статистику прогона. */
	private function rescore($run) {
		$scores = $this->computeScores($run['run_id']);

		foreach (array('stats', 'core') as $key) {
			if (isset($run['scores'][$key])) {
				$scores[$key] = $run['scores'][$key];
			}
		}

		$this->model()->updateScores($run['run_id'], $scores);

		return $scores;
	}

	private function computeScores($run_id) {
		$this->loadScanner();
		$issues = array();

		foreach ($this->model()->getIssues($run_id) as $issue) {
			if (!$issue['ignored']) {
				$issues[] = $issue;
			}
		}

		$applicable = array('security' => true, 'seo' => true, 'lang' => true);

		return KriatizAuditScanner::scores($issues, $applicable);
	}

	public function results() {
		$this->load->language(self::ROUTE);
		$run = $this->model()->getLastRun('done');
		$this->json(array('run' => $run ? $this->runPayload($run) : null));
	}

	/* ------------------------------------------------------------------ */
	/* Виключення                                                          */
	/* ------------------------------------------------------------------ */

	public function ignore() {
		$this->load->language(self::ROUTE);

		if (!$this->canModify()) {
			$this->json(array('error' => $this->language->get('error_permission')));

			return;
		}

		$model = $this->model();
		$issue = $model->getIssue(isset($this->request->post['issue_id']) ? (int)$this->request->post['issue_id'] : 0);

		if (!$issue) {
			$this->json(array('error' => $this->language->get('js_error_run')));

			return;
		}

		$model->ignore($issue['hash'], $issue['code'], $issue['target'], (int)$this->user->getId());
		$scores = $this->rescore($model->getRun($issue['run_id']));
		$this->json(array('success' => true, 'scores' => $scores));
	}

	public function unignore() {
		$this->load->language(self::ROUTE);

		if (!$this->canModify()) {
			$this->json(array('error' => $this->language->get('error_permission')));

			return;
		}

		$model = $this->model();
		$hash = isset($this->request->post['hash']) ? preg_replace('/[^a-f0-9]/', '', (string)$this->request->post['hash']) : '';
		$model->unignore($hash);
		$last = $model->getLastRun('done');
		$scores = array();

		if ($last) {
			$scores = $this->rescore($last);
		}

		$this->json(array('success' => true, 'scores' => $scores));
	}

	/* ------------------------------------------------------------------ */
	/* Звіт у файл                                                         */
	/* ------------------------------------------------------------------ */

	public function export() {
		$this->load->language(self::ROUTE);
		$model = $this->model();
		$run = $model->getLastRun('done');
		$lines = array();
		$lines[] = 'KRIATIZ Audit ' . self::VERSION . ' — ' . ($run ? $run['date_end'] : '');
		$lines[] = 'OpenCart ' . VERSION . ', PHP ' . PHP_VERSION;

		if ($run) {
			$s = $run['scores'];
			$lines[] = $this->language->get('block_total') . ': ' . (isset($s['total']) ? $s['total'] : '-') . ' | ' . $this->language->get('block_security') . ': ' . (isset($s['security']) ? $s['security'] : '-') . ' | SEO: ' . (isset($s['seo']) ? $s['seo'] : '-') . ' | ' . $this->language->get('block_lang') . ': ' . (isset($s['lang']) ? $s['lang'] : '-');
			$lines[] = str_repeat('=', 70);

			foreach ($model->getIssues($run['run_id']) as $issue) {
				$title = $this->language->get('check_' . $issue['code'] . '_title');
				$line = '[' . strtoupper($issue['level']) . '] ' . ($title !== 'check_' . $issue['code'] . '_title' ? $title : $issue['code']);

				if ($issue['target'] && strpos($issue['target'], 'user:') !== 0 && $issue['target'] !== 'checkout') {
					$line .= ' — ' . $issue['target'];
				}

				if ($issue['ignored']) {
					$line .= ' (' . $this->language->get('js_ignored') . ')';
				}

				$lines[] = $line;
				$data = $issue['data'];

				if (!empty($data['line'])) {
					// Без фрагмента кода: антивирус на компьютере блокирует скачивание файла с ним
					$lines[] = '    line ' . (int)$data['line'];
				}

				foreach (array('hosts', 'list', 'words') as $k) {
					if (!empty($data[$k]) && is_array($data[$k])) {
						$lines[] = '    ' . implode(', ', array_slice($data[$k], 0, 30));
					}
				}

				if (!empty($data['count'])) {
					$lines[] = '    count: ' . (int)$data['count'] . (isset($data['total']) ? ' / ' . (int)$data['total'] : '');
				}
			}
		}

		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="kriatiz-audit-' . date('Y-m-d') . '.txt"');
		$this->response->setOutput(implode("\r\n", $lines));
	}

	/* ------------------------------------------------------------------ */
	/* Встановлення                                                        */
	/* ------------------------------------------------------------------ */

	public function install() {
		$this->model()->install();
	}

	public function uninstall() {
		$this->model()->uninstall();
	}
}
