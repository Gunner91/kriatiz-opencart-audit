/* KRIATIZ Аудит — інтерфейс */
jQuery(function ($) {
	var K = window.KAU || {};
	var T = K.texts || {};
	var run = K.last;
	var busy = false;

	function t(key, fallback) { return T[key] !== undefined ? T[key] : (fallback !== undefined ? fallback : key); }
	function esc(s) { return $('<div>').text(s === undefined || s === null ? '' : String(s)).html(); }
	function tokenQuery() { var m = (K.urls.start || '').match(/(user_token|token)=([^&]+)/); return m ? m[1] + '=' + m[2] : ''; }
	function adminLink(route, args) { return 'index.php?route=' + route + (args ? '&' + args : '') + '&' + tokenQuery(); }

	var LEVELS = { critical: 3, warning: 2, info: 1 };

	/* ---------- Оцінки ---------- */
	function setRing(name, value) {
		var $r = $('[data-kau-score="' + name + '"]');
		var $b = $r.find('b');
		var fg = $r.find('.kau-ring-fg')[0];
		var len = 2 * Math.PI * 52;
		$r.removeClass('kau-good kau-mid kau-bad kau-na');

		if (value === null || value === undefined) {
			$b.text('—');
			$r.addClass('kau-na');
			if (fg) { fg.style.strokeDasharray = len; fg.style.strokeDashoffset = len; }
			return;
		}

		$b.text(value);
		$r.addClass(value >= 80 ? 'kau-good' : (value >= 50 ? 'kau-mid' : 'kau-bad'));
		if (fg) { fg.style.strokeDasharray = len; fg.style.strokeDashoffset = len * (1 - value / 100); }
	}

	function renderScores(scores) {
		scores = scores || {};
		setRing('total', scores.total);
		setRing('security', scores.security);
		setRing('seo', scores.seo);
		setRing('lang', scores.lang);
	}

	/* ---------- Знахідки ---------- */
	function activeIssues(block) {
		return (run && run.issues ? run.issues : []).filter(function (i) { return (!block || i.block === block); });
	}

	function counts(block) {
		var c = { critical: 0, warning: 0, info: 0 };
		activeIssues(block).forEach(function (i) { if (!i.ignored && c[i.level] !== undefined) { c[i.level]++; } });
		return c;
	}

	function targetHtml(issue) {
		var tg = issue.target || '';
		if (!tg || /^user:/.test(tg) || tg === 'checkout' || tg === 'core' || tg === 'home' || tg === 'root' || tg === 'setting' || tg === 'seo_url' || tg === 'url_alias') { return ''; }
		var p = tg.split(':');
		var map = {
			product_description: ['catalog/product/edit', 'product_id', t('js_target_product')],
			category_description: ['catalog/category/edit', 'category_id', t('js_target_category')],
			information_description: ['catalog/information/edit', 'information_id', t('js_target_information')],
			manufacturer_description: ['catalog/manufacturer/edit', 'manufacturer_id', t('js_target_manufacturer')],
			product: ['catalog/product/edit', 'product_id', t('js_target_product')]
		};

		if (map[p[0]] && p[1] && /^\d+$/.test(p[1])) {
			var m = map[p[0]];
			return '<a href="' + esc(adminLink(m[0], m[1] + '=' + p[1])) + '" target="_blank">' + esc(m[2]) + ' #' + esc(p[1]) + '</a>' + (p[p.length - 1] && p.length > 2 ? ' <span class="kau-muted">(' + esc(p[p.length - 1]) + ')</span>' : '');
		}

		if (p[0] === 'setting' && p.length > 2) {
			return '<a href="' + esc(adminLink('setting/setting')) + '" target="_blank">' + esc(t('js_target_setting')) + '</a> <code>' + esc(p[2]) + '</code>';
		}

		if (p[0] === 'modification') {
			return esc(t('js_target_modification')) + ' <b>' + esc(p[2] || ('#' + p[1])) + '</b>';
		}

		if (p[0] === 'module' || p[0] === 'banner_image' || p[0] === 'review' || p[0] === 'article_description' || p[0] === 'blog_category_description') {
			return '<code>' + esc(p[0] + ' #' + p[1]) + '</code>';
		}

		return '<code>' + esc(tg) + '</code>';
	}

	function detailsHtml(issue) {
		var d = issue.data || {};
		var out = [];

		if (d.snippet) {
			out.push('<div class="kau-code"><span class="kau-code-line">' + (d.line ? esc(t('js_line')) + ' ' + esc(d.line) : '') + '</span><code>' + esc(d.snippet) + '</code></div>');
		}

		if (d.hosts && d.hosts.length) {
			out.push('<div>' + esc(t('js_hosts')) + ' ' + d.hosts.map(function (h) { return '<span class="kau-tag kau-tag-red">' + esc(h) + '</span>'; }).join(' ') + '</div>');
		}

		if (d.words && d.words.length) {
			out.push('<div>' + esc(t('js_words')) + ' ' + d.words.map(function (h) { return '<span class="kau-tag kau-tag-red">' + esc(h) + '</span>'; }).join(' ') + '</div>');
		}

		if (d.location) { out.push('<div>' + esc(t('js_location')) + ' <code>' + esc(d.location) + '</code></div>'); }

		if (d.counts) {
			var parts = [];
			$.each(d.counts, function (k, v) { parts.push('<span class="kau-tag">' + esc(k) + ': ' + esc(v) + '</span>'); });
			out.push('<div>' + parts.join(' ') + '</div>');
		}

		if (d.count && !d.counts) {
			out.push('<div>' + esc(t('js_count')) + ' <b>' + esc(d.count) + '</b>' + (d.total ? ' ' + esc(t('js_of')) + ' ' + esc(d.total) + (d.percent !== undefined ? ' (' + esc(d.percent) + '%)' : '') : '') + '</div>');
		}

		if (d.version) { out.push('<div>' + esc(t('js_version')) + ' <b>' + esc(d.version) + '</b></div>'); }
		if (d['default']) { out.push('<div>' + esc(t('js_default_lang')) + ' <b>' + esc(d['default']) + '</b></div>'); }
		if (d.base) { out.push('<div class="kau-muted">' + esc(t('js_core_base')) + ' ' + esc(d.base) + '</div>'); }
		if (d.error) { out.push('<div class="kau-muted">' + esc(d.error) + '</div>'); }

		if (d.list && d.list.length) {
			var items = d.list.slice(0, 60).map(function (x) { return '<li><code>' + esc(x) + '</code></li>'; }).join('');
			out.push('<ul class="kau-list">' + items + '</ul>' + (d.list.length > 60 ? '<div class="kau-muted">+' + (d.list.length - 60) + '</div>' : ''));
		}

		return out.join('');
	}

	function ctaHtml(issue) {
		var code = issue.code;
		if (issue.block === 'lang' && /^(lang_one_url|lang_no_hreflang|lang_default_not_uk|lang_untranslated)$/.test(code)) {
			return '<a class="btn btn-primary btn-xs" href="' + esc(K.urls.multilang) + '" target="_blank" rel="noopener"><i class="fa fa-magic"></i> ' + esc(t('js_cta_multilang')) + '</a>';
		}
		if (issue.block === 'seo' && /^(seo_url_bad_chars|seo_url_missing)$/.test(code)) {
			return '<a class="btn btn-default btn-xs" href="' + esc(K.urls.multilang) + '" target="_blank" rel="noopener"><i class="fa fa-magic"></i> ' + esc(t('js_cta_multilang_seo')) + '</a>';
		}
		if (issue.block === 'seo' && /^(meta_title_empty|meta_description_empty|description_empty|meta_title_duplicate)$/.test(code)) {
			return '<span class="kau-soon-inline"><i class="fa fa-magic"></i> ' + esc(t('js_cta_ai_soon')) + '</span>';
		}
		if (issue.block === 'security' && issue.level === 'critical') {
			return '<a class="btn btn-danger btn-xs" href="' + esc(K.urls.author) + '" target="_blank" rel="noopener"><i class="fa fa-medkit"></i> ' + esc(t('js_cta_cure')) + '</a>';
		}
		return '';
	}

	function issueHtml(issue) {
		var title = t('check_' + issue.code + '_title', issue.code);
		var why = T['check_' + issue.code + '_why'] || '';
		var fix = T['check_' + issue.code + '_fix'] || '';
		var tgt = targetHtml(issue);
		var cta = ctaHtml(issue);
		var ignoreBtn = K.canModify ? (issue.ignored
			? '<span class="kau-muted"><i class="fa fa-eye-slash"></i> ' + esc(t('js_ignored')) + '</span>'
			: '<button type="button" class="btn btn-link btn-xs kau-ignore" data-kau-ignore="' + issue.issue_id + '"><i class="fa fa-eye-slash"></i> ' + esc(t('js_not_threat')) + '</button>') : '';

		return '<div class="kau-issue kau-' + issue.level + (issue.ignored ? ' kau-is-ignored' : '') + '" data-level="' + issue.level + '">' +
			'<div class="kau-issue-head" data-kau-toggle>' +
				'<span class="kau-level kau-level-' + issue.level + '">' + esc(t('level_' + issue.level)) + '</span>' +
				'<div class="kau-issue-title"><b>' + esc(title) + '</b>' + (tgt ? '<div class="kau-target">' + tgt + '</div>' : '') + '</div>' +
				'<i class="fa fa-chevron-down kau-chevron"></i>' +
			'</div>' +
			'<div class="kau-issue-body">' +
				(why ? '<p><b>' + esc(t('js_why')) + '</b> ' + why + '</p>' : '') +
				detailsHtml(issue) +
				(fix ? '<p class="kau-fix"><b>' + esc(t('js_fix')) + '</b> ' + fix + '</p>' : '') +
				'<div class="kau-issue-actions">' + cta + ' ' + ignoreBtn + '</div>' +
			'</div>' +
		'</div>';
	}

	function sortIssues(list) {
		return list.slice().sort(function (a, b) {
			if (a.ignored !== b.ignored) { return a.ignored ? 1 : -1; }
			return (LEVELS[b.level] || 0) - (LEVELS[a.level] || 0);
		});
	}

	function renderList(block) {
		var $box = $('[data-kau-list="' + block + '"]');
		var list = sortIssues(activeIssues(block));

		if (!run) { return; }

		if (!list.length) {
			$box.html('<div class="kau-allgood"><i class="fa fa-check-circle"></i> ' + esc(t('js_block_clean')) + '</div>');
			return;
		}

		$box.html(list.map(issueHtml).join(''));
		applyFilter(block);
	}

	function applyFilter(block) {
		var level = $('[data-kau-filter="' + block + '"] .active').data('level') || 'all';
		$('[data-kau-list="' + block + '"] .kau-issue').each(function () {
			$(this).toggle(level === 'all' || $(this).data('level') === level);
		});
	}

	function renderOverview() {
		var $o = $('[data-kau-overview]');
		if (!run) { return; }

		var blocks = [
			{ key: 'security', icon: 'fa-bug', name: t('block_security') },
			{ key: 'seo', icon: 'fa-search', name: 'SEO' },
			{ key: 'lang', icon: 'fa-language', name: t('block_lang') }
		];
		var html = '<div class="kau-blocks">';

		blocks.forEach(function (b) {
			var c = counts(b.key);
			var score = run.scores ? run.scores[b.key] : null;
			html += '<a href="#kau-tab-' + b.key + '" class="kau-block" data-kau-open="' + b.key + '">' +
				'<div class="kau-block-head"><i class="fa ' + b.icon + '"></i> ' + esc(b.name) + '<span class="kau-block-score ' + (score >= 80 ? 'kau-good' : (score >= 50 ? 'kau-mid' : 'kau-bad')) + '">' + (score === null || score === undefined ? '—' : esc(score)) + '</span></div>' +
				'<div class="kau-block-counts"><span class="kau-c-critical">' + c.critical + ' ' + esc(t('level_critical')) + '</span><span class="kau-c-warning">' + c.warning + ' ' + esc(t('level_warning')) + '</span><span class="kau-c-info">' + c.info + ' ' + esc(t('level_info')) + '</span></div>' +
			'</a>';
		});

		html += '</div>';

		var top = sortIssues(activeIssues().filter(function (i) { return !i.ignored && i.level === 'critical'; })).slice(0, 8);

		if (top.length) {
			html += '<h4 class="kau-h4"><i class="fa fa-exclamation-triangle text-danger"></i> ' + esc(t('js_top_critical')) + '</h4>' + top.map(issueHtml).join('');
		} else {
			html += '<div class="kau-allgood"><i class="fa fa-check-circle"></i> ' + esc(t('js_no_critical')) + '</div>';
		}

		var st = run.scores && run.scores.stats ? run.scores.stats : {};
		html += '<div class="kau-checked"><h4 class="kau-h4"><i class="fa fa-list-alt"></i> ' + esc(t('js_checked')) + '</h4><ul>' +
			'<li>' + esc(t('js_checked_files')) + ' <b>' + esc(st.files || 0) + '</b></li>' +
			'<li>' + esc(t('js_checked_rows')) + ' <b>' + esc(st.rows || 0) + '</b></li>' +
			'<li>' + esc(t('js_checked_core')) + ' <b>' + esc(run.scores && run.scores.core ? run.scores.core : t('js_core_unknown')) + '</b></li>' +
			'<li>' + esc(t('js_checked_pages')) + '</li>' +
		'</ul></div>';

		$o.html(html);
	}

	function renderHeader() {
		if (!run) { return; }
		var c = counts();
		var total = run.scores ? run.scores.total : null;
		var verdict = c.critical ? t('js_verdict_bad') : (total >= 80 ? t('js_verdict_good') : t('js_verdict_mid'));
		$('[data-kau-verdict]').text(verdict);
		$('[data-kau-meta]').text(t('js_last_scan') + ' ' + (run.date_end || ''));
		$('[data-kau-counters]').html('<span class="kau-c-critical">' + c.critical + ' ' + esc(t('level_critical')) + '</span><span class="kau-c-warning">' + c.warning + ' ' + esc(t('level_warning')) + '</span><span class="kau-c-info">' + c.info + ' ' + esc(t('level_info')) + '</span>');

		['security', 'seo', 'lang'].forEach(function (b) {
			var cb = counts(b);
			var n = cb.critical + cb.warning;
			$('[data-kau-badge="' + b + '"]').text(n || '').toggleClass('kau-badge-red', cb.critical > 0);
		});

		$('[data-kau-export]').removeAttr('disabled');
		$('[data-kau-start] span').text(t('js_rescan'));
	}

	function renderAll() {
		renderScores(run ? run.scores : null);
		renderHeader();
		renderOverview();
		['security', 'seo', 'lang'].forEach(renderList);
	}

	/* ---------- Запуск перевірки ---------- */
	function setPhase(phase) {
		var order = ['config', 'core', 'files', 'database', 'frontend', 'seo', 'lang'];
		var idx = order.indexOf(phase);
		$('[data-kau-phases] span').each(function (i) {
			var $s = $(this);
			$s.removeClass('kau-ph-done kau-ph-now');
			if (i < idx || phase === 'done') { $s.addClass('kau-ph-done').find('i').attr('class', 'fa fa-check-circle'); }
			else if (i === idx) { $s.addClass('kau-ph-now').find('i').attr('class', 'fa fa-spinner fa-spin'); }
			else { $s.find('i').attr('class', 'fa fa-circle-o'); }
		});
		$('[data-kau-phase]').text(t('phase_' + phase, ''));
	}

	function progress(p, data) {
		var $w = $('[data-kau-progress]').show();
		$w.find('.progress-bar').css('width', p + '%');
		$('[data-kau-percent]').text(p + '%');
		if (data) {
			setPhase(data.phase || 'config');
			$('[data-kau-stats]').text(t('js_stats').replace('%files%', data.files || 0).replace('%rows%', data.rows || 0));
		}
	}

	function step(runId, errors) {
		$.post(K.urls.step, { run_id: runId }, null, 'json').done(function (r) {
			if (r.error) { finish(r.error); return; }
			if (r.done) {
				run = r.run;
				progress(100, { phase: 'done' });
				finish();
				renderAll();
				return;
			}
			progress(r.progress, r);
			step(runId, 0);
		}).fail(function () {
			errors = (errors || 0) + 1;
			if (errors > 4) { finish(t('js_error_net')); return; }
			setTimeout(function () { step(runId, errors); }, 1500 * errors);
		});
	}

	function finish(error) {
		busy = false;
		$('[data-kau-start]').prop('disabled', false).find('i').attr('class', 'fa fa-shield');
		if (error) { alert(error); }
		setTimeout(function () { $('[data-kau-progress]').slideUp(); }, error ? 0 : 1200);
	}

	function startScan() {
		if (busy) { return; }
		busy = true;
		$('[data-kau-start]').prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin');
		progress(1, { phase: 'config' });
		$.post(K.urls.start, {}, null, 'json').done(function (r) {
			if (r.cooldown) { finish(r.message); return; }
			if (!r.run_id) { finish(r.error || t('js_error_net')); return; }
			step(r.run_id, 0);
		}).fail(function () { finish(t('js_error_net')); });
	}

	$(document).on('click', '[data-kau-start]', startScan);

	/* ---------- Взаємодія ---------- */
	$(document).on('click', '[data-kau-toggle]', function () {
		$(this).closest('.kau-issue').toggleClass('kau-open');
	});

	$(document).on('click', '[data-kau-filter] button', function () {
		var $f = $(this).closest('[data-kau-filter]');
		$f.find('button').removeClass('active');
		$(this).addClass('active');
		applyFilter($f.data('kau-filter'));
	});

	$(document).on('click', '[data-kau-open]', function (e) {
		e.preventDefault();
		$('.kau-tabs a[href="#kau-tab-' + $(this).data('kau-open') + '"]').tab('show');
	});

	$(document).on('click', '[data-kau-ignore]', function () {
		var id = $(this).data('kau-ignore');
		var $btn = $(this).prop('disabled', true);
		$.post(K.urls.ignore, { issue_id: id }, null, 'json').done(function (r) {
			if (r.error) { alert(r.error); $btn.prop('disabled', false); return; }
			run.issues.forEach(function (i) { if (String(i.issue_id) === String(id)) { i.ignored = true; } });
			if (r.scores) { $.extend(run.scores, r.scores); }
			renderAll();
		});
	});

	$(document).on('click', '[data-kau-unignore]', function () {
		var hash = $(this).data('kau-unignore');
		var $row = $(this).closest('tr');
		$.post(K.urls.unignore, { hash: hash }, null, 'json').done(function (r) {
			if (r.error) { alert(r.error); return; }
			$row.remove();
			if (run) {
				run.issues.forEach(function (i) { if (i.hash === hash) { i.ignored = false; } });
				if (r.scores) { $.extend(run.scores, r.scores); }
				renderAll();
			}
		});
	});

	/* ---------- Старт ---------- */
	renderAll();

	if (K.running) {
		busy = true;
		$('[data-kau-start]').prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin');
		progress(1, { phase: 'config' });
		step(K.running, 0);
	}
});
