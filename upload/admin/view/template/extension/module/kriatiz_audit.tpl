<?php
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$config = array(
	'urls'      => $urls,
	'texts'     => $texts,
	'last'      => $last,
	'running'   => $running_id,
	'canModify' => (bool)$can_modify,
);
?>
<?php echo $header; ?><?php echo $column_left; ?>
<div id="content" class="kau">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right kau-head-actions">
				<a href="<?php echo $h($urls['export']); ?>" class="btn btn-default" data-kau-export <?php echo $last ? '' : 'disabled'; ?>><i class="fa fa-download"></i> <?php echo $h($t['button_export']); ?></a>
				<a href="<?php echo $h($urls['back']); ?>" class="btn btn-default" title="<?php echo $h($t['button_back']); ?>"><i class="fa fa-reply"></i></a>
			</div>
			<h1><span class="kau-logo">K</span> <?php echo $h($t['heading_title_plain']); ?> <small><?php echo $h($version); ?></small></h1>
			<ul class="breadcrumb">
				<?php foreach ($breadcrumbs as $b) { ?>
				<li><a href="<?php echo $h($b['href']); ?>"><?php echo $h($b['text']); ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</div>

	<div class="container-fluid">
		<?php if (!$can_modify) { ?>
		<div class="alert alert-info"><i class="fa fa-eye"></i> <?php echo $h($t['text_readonly']); ?></div>
		<?php } ?>

		<div class="kau-hero">
			<div class="kau-hero-main">
				<div class="kau-rings">
					<div class="kau-ring kau-ring-lg" data-kau-score="total"><svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="52" class="kau-ring-bg"/><circle cx="60" cy="60" r="52" class="kau-ring-fg"/></svg><div><b>—</b><small><?php echo $h($t['block_total']); ?></small></div></div>
					<div class="kau-ring" data-kau-score="security"><svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="52" class="kau-ring-bg"/><circle cx="60" cy="60" r="52" class="kau-ring-fg"/></svg><div><b>—</b><small><?php echo $h($t['block_security']); ?></small></div></div>
					<div class="kau-ring" data-kau-score="seo"><svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="52" class="kau-ring-bg"/><circle cx="60" cy="60" r="52" class="kau-ring-fg"/></svg><div><b>—</b><small>SEO</small></div></div>
					<div class="kau-ring" data-kau-score="lang"><svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="52" class="kau-ring-bg"/><circle cx="60" cy="60" r="52" class="kau-ring-fg"/></svg><div><b>—</b><small><?php echo $h($t['block_lang']); ?></small></div></div>
				</div>
				<div class="kau-hero-text">
					<h3 data-kau-verdict><?php echo $h($t['text_hero_title']); ?></h3>
					<p class="kau-muted" data-kau-meta><?php echo $h($t['text_hero_lead']); ?></p>
					<div class="kau-counters" data-kau-counters></div>
					<button type="button" class="btn btn-primary btn-lg" data-kau-start><i class="fa fa-shield"></i> <span><?php echo $h($last ? $t['button_rescan'] : $t['button_scan']); ?></span></button>
					<span class="kau-muted kau-time-hint"><?php echo $h($t['text_scan_time']); ?></span>
				</div>
			</div>
			<div class="kau-progress-wrap" data-kau-progress style="display:none">
				<div class="kau-progress-head"><b data-kau-phase></b><span data-kau-percent>0%</span></div>
				<div class="progress kau-progress"><div class="progress-bar progress-bar-striped active" style="width:0%"></div></div>
				<div class="kau-phases" data-kau-phases>
					<?php foreach (array('config', 'core', 'files', 'database', 'frontend', 'seo', 'lang') as $p) { ?>
					<span data-phase="<?php echo $p; ?>"><i class="fa fa-circle-o"></i> <?php echo $h($t['phase_' . $p]); ?></span>
					<?php } ?>
				</div>
				<p class="kau-muted" data-kau-stats></p>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-9 col-md-8">
				<ul class="nav nav-tabs kau-tabs">
					<li class="active"><a href="#kau-tab-overview" data-toggle="tab"><i class="fa fa-dashboard"></i> <?php echo $h($t['tab_overview']); ?></a></li>
					<li><a href="#kau-tab-security" data-toggle="tab"><i class="fa fa-bug"></i> <?php echo $h($t['block_security']); ?> <span class="badge" data-kau-badge="security"></span></a></li>
					<li><a href="#kau-tab-seo" data-toggle="tab"><i class="fa fa-search"></i> SEO <span class="badge" data-kau-badge="seo"></span></a></li>
					<li><a href="#kau-tab-lang" data-toggle="tab"><i class="fa fa-language"></i> <?php echo $h($t['block_lang']); ?> <span class="badge" data-kau-badge="lang"></span></a></li>
					<li><a href="#kau-tab-ignored" data-toggle="tab"><i class="fa fa-eye-slash"></i> <?php echo $h($t['tab_ignored']); ?></a></li>
					<li><a href="#kau-tab-help" data-toggle="tab"><i class="fa fa-question-circle"></i> <?php echo $h($t['tab_help']); ?></a></li>
				</ul>
				<div class="tab-content kau-tab-content">
					<div class="tab-pane active" id="kau-tab-overview">
						<div data-kau-overview><p class="kau-empty"><i class="fa fa-shield fa-3x"></i><br><?php echo $h($t['text_no_scan']); ?></p></div>
					</div>
					<?php foreach (array('security', 'seo', 'lang') as $block) { ?>
					<div class="tab-pane" id="kau-tab-<?php echo $block; ?>">
						<div class="kau-filter" data-kau-filter="<?php echo $block; ?>">
							<button type="button" class="btn btn-default btn-sm active" data-level="all"><?php echo $h($t['filter_all']); ?></button>
							<button type="button" class="btn btn-default btn-sm" data-level="critical"><?php echo $h($t['level_critical']); ?></button>
							<button type="button" class="btn btn-default btn-sm" data-level="warning"><?php echo $h($t['level_warning']); ?></button>
							<button type="button" class="btn btn-default btn-sm" data-level="info"><?php echo $h($t['level_info']); ?></button>
						</div>
						<div data-kau-list="<?php echo $block; ?>"><p class="kau-empty"><?php echo $h($t['text_no_scan']); ?></p></div>
					</div>
					<?php } ?>
					<div class="tab-pane" id="kau-tab-ignored">
						<p class="kau-lead"><?php echo $h($t['text_ignored_lead']); ?></p>
						<div data-kau-ignored>
							<?php if (!$ignored) { ?><p class="kau-empty"><?php echo $h($t['text_ignored_empty']); ?></p><?php } else { ?>
							<table class="table table-hover">
								<tbody>
									<?php foreach ($ignored as $row) { ?>
									<tr>
										<td><b><?php echo $h(isset($t['check_' . $row['code'] . '_title']) ? $t['check_' . $row['code'] . '_title'] : $row['code']); ?></b><br><code><?php echo $h($row['target']); ?></code></td>
										<td class="text-muted"><?php echo $h($row['date_added']); ?></td>
										<td class="text-right"><?php if ($can_modify) { ?><button type="button" class="btn btn-default btn-sm" data-kau-unignore="<?php echo $h($row['hash']); ?>"><i class="fa fa-undo"></i> <?php echo $h($t['button_unignore']); ?></button><?php } ?></td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
							<?php } ?>
						</div>
					</div>
					<div class="tab-pane kau-help" id="kau-tab-help">
						<?php echo $t['text_help']; ?>
					</div>
				</div>
			</div>

			<div class="col-lg-3 col-md-4">
				<div class="kau-side">
					<div class="kau-side-card kau-side-pro">
						<span class="kau-pill"><?php echo $h($t['pro_badge']); ?></span>
						<h4><i class="fa fa-medkit"></i> <?php echo $h($t['pro_cure_title']); ?></h4>
						<p><?php echo $h($t['pro_cure_text']); ?></p>
						<span class="kau-soon"><?php echo $h($t['pro_soon']); ?></span>
					</div>
					<div class="kau-side-card">
						<span class="kau-pill"><?php echo $h($t['pro_badge']); ?></span>
						<h4><i class="fa fa-bell"></i> <?php echo $h($t['pro_watch_title']); ?></h4>
						<p><?php echo $h($t['pro_watch_text']); ?></p>
						<span class="kau-soon"><?php echo $h($t['pro_soon']); ?></span>
					</div>
					<div class="kau-side-card kau-side-help">
						<h4><i class="fa fa-life-ring"></i> <?php echo $h($t['pro_service_title']); ?></h4>
						<p><?php echo $h($t['pro_service_text']); ?></p>
						<a href="<?php echo $h($urls['author']); ?>" target="_blank" rel="noopener" class="btn btn-danger btn-block"><?php echo $h($t['pro_service_button']); ?></a>
					</div>
					<div class="kau-side-card">
						<h4><i class="fa fa-language"></i> KRIATIZ Multilang SEO</h4>
						<p><?php echo $h($t['side_multilang']); ?></p>
						<a href="<?php echo $h($urls['multilang']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-block"><?php echo $h($t['side_more']); ?></a>
					</div>
					<p class="kau-muted kau-disclaimer"><?php echo $h($t['text_disclaimer']); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">window.KAU = <?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;</script>
<?php echo $footer; ?>
