<?php
// KRIATIZ Audit — English
$_['heading_title']       = '<span style="display:inline-flex;align-items:center;gap:6px"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:5px;background:linear-gradient(135deg,#2b1b6b,#6c3bff);color:#fff;font-weight:700;font-size:12px">K</span>KRIATIZ Audit: malware, hacks, SEO and language</span>';
$_['heading_title_plain'] = 'KRIATIZ Audit';

$_['text_home']           = 'Home';
$_['text_extension']      = 'Extensions';
$_['text_readonly']       = 'Demo mode: you can run a scan and view results, but cannot mark findings as «not a threat».';
$_['text_hero_title']     = 'Check whether your store has been hacked';
$_['text_hero_lead']      = 'Web shells and malware in files, malicious code in the database, checkout skimmers, SEO spam shown to Google, exposed service files, plus SEO and the Ukrainian language law. The module changes nothing in your store.';
$_['text_scan_time']      = 'Usually 1–5 minutes';
$_['text_no_scan']        = 'No scan yet. Click «Scan store».';
$_['text_ignored_lead']   = 'Findings you marked «Not a threat». They do not affect the score. Restore them if you made a mistake.';
$_['text_ignored_empty']  = 'No exclusions.';
$_['text_disclaimer']     = 'The audit finds known signs of hacking and configuration mistakes. It does not guarantee the absence of all malicious code and does not replace backups and server protection.';

$_['button_scan']         = 'Scan store';
$_['button_rescan']       = 'Scan again';
$_['button_export']       = 'Report to file';
$_['button_back']         = 'Back';
$_['button_unignore']     = 'Restore';

$_['tab_overview']        = 'Overview';
$_['tab_ignored']         = 'Exclusions';
$_['tab_help']            = 'Help';
$_['filter_all']          = 'All';

$_['block_total']         = 'Overall score';
$_['block_security']      = 'Security';
$_['block_lang']          = 'Language';

$_['level_critical']      = 'Critical';
$_['level_warning']       = 'Recommended';
$_['level_info']          = 'For info';

$_['phase_config']        = 'Settings';
$_['phase_core']          = 'OpenCart core';
$_['phase_files']         = 'All files';
$_['phase_database']      = 'Database';
$_['phase_frontend']      = 'Storefront as Google sees it';
$_['phase_seo']           = 'SEO';
$_['phase_lang']          = 'Language and law';
$_['phase_done']          = 'Done';

$_['pro_badge']           = 'PRO';
$_['pro_soon']            = 'Coming soon as a paid option';
$_['pro_cure_title']      = 'One-click cleanup';
$_['pro_cure_text']       = 'Quarantine infected files, restore original core files, clean malicious code in the database. A backup and an «Undo» button before every action.';
$_['pro_watch_title']     = 'Monitoring 24/7';
$_['pro_watch_text']      = 'Scheduled scans and Telegram / e-mail alerts as soon as a new file appears, the core changes or a new administrator is created.';
$_['pro_service_title']   = 'Already hacked?';
$_['pro_service_text']    = 'We will clean the site for you: find every infected place, the cause of the hack, and close the hole.';
$_['pro_service_button']  = 'Order a cleanup';
$_['side_multilang']      = 'Separate URLs per language (/ru/, /en/), hreflang and Ukrainian by default — fixes the «Language» findings.';
$_['side_more']           = 'Learn more';

$_['error_permission']    = 'You do not have permission to modify this module.';

$_['js_rescan']           = 'Scan again';
$_['js_cooldown']         = 'In demo mode a scan can run once every 15 minutes. Results of the last scan are shown below.';
$_['js_error_run']        = 'Scan not found. Please run it again.';
$_['js_error_net']        = 'The server did not respond. Try again or post in the support topic.';
$_['js_stats']            = 'Files checked: %files%, database rows: %rows%';
$_['js_last_scan']        = 'Last scan:';
$_['js_verdict_bad']      = 'Critical problems found — look at them first';
$_['js_verdict_mid']      = 'No critical threats, but there is room for improvement';
$_['js_verdict_good']     = 'Your store is in good shape';
$_['js_top_critical']     = 'Critical findings';
$_['js_no_critical']      = 'No critical threats found.';
$_['js_block_clean']      = 'No problems found in this section.';
$_['js_checked']          = 'What was checked';
$_['js_checked_files']    = 'Site files:';
$_['js_checked_rows']     = 'Database rows:';
$_['js_checked_core']     = 'Core compared with the original:';
$_['js_checked_pages']    = 'Home, product and checkout pages — as a visitor, Googlebot and a smartphone see them';
$_['js_core_unknown']     = 'version not detected';
$_['js_why']              = 'Why it matters:';
$_['js_fix']              = 'How to fix:';
$_['js_line']             = 'line';
$_['js_hosts']            = 'Domains:';
$_['js_words']            = 'Words:';
$_['js_location']         = 'Redirects to:';
$_['js_count']            = 'Count:';
$_['js_of']               = 'of';
$_['js_version']          = 'Version:';
$_['js_default_lang']     = 'Current default language:';
$_['js_core_base']        = 'Compared with:';
$_['js_not_threat']       = 'Not a threat';
$_['js_ignored']          = 'marked as not a threat';
$_['js_cta_multilang']    = 'Fix with KRIATIZ Multilang SEO';
$_['js_cta_multilang_seo']= 'SEO URL autofill in Multilang SEO';
$_['js_cta_ai_soon']      = 'KRIATIZ AI SEO will fill this automatically — coming soon';
$_['js_cta_cure']         = 'Order a cleanup';
$_['js_target_product']   = 'Product';
$_['js_target_category']  = 'Category';
$_['js_target_information'] = 'Page';
$_['js_target_manufacturer'] = 'Manufacturer';
$_['js_target_setting']   = 'Store settings';
$_['js_target_modification'] = 'Modification';

$_['check_install_dir_title'] = 'The install folder is still on the server';
$_['check_install_dir_why']   = 'The installer can be used to reinstall the store and gain access to the database.';
$_['check_install_dir_fix']   = 'Delete the installer folder in the site root via FTP or your hosting file manager.';
$_['check_config_writable_title'] = 'config.php files are writable';
$_['check_config_writable_why']   = 'If an attacker gets access, they can change database settings and URLs.';
$_['check_config_writable_fix']   = 'Set permissions 444 or 644 for config.php and admin/config.php.';
$_['check_admin_default_path_title'] = 'Admin panel at the default /admin URL';
$_['check_admin_default_path_why']   = 'Bots brute-force passwords exactly at /admin.';
$_['check_admin_default_path_fix']   = 'Rename the admin folder and update paths in admin/config.php, or restrict /admin by IP or a server password.';
$_['check_php_old_title'] = 'Outdated PHP version';
$_['check_php_old_why']   = 'This PHP version receives no security updates.';
$_['check_php_old_fix']   = 'Switch to PHP 7.4 or newer in your hosting panel after testing modules on a copy of the site.';
$_['check_php_eol_title'] = 'PHP without security updates';
$_['check_php_eol_why']   = 'This PHP version no longer receives security fixes.';
$_['check_php_eol_fix']   = 'When possible, move to PHP 8.2 or 8.3. Test modules on a copy of the site first.';
$_['check_oc_branch_old_title'] = 'The OpenCart 2.x branch is no longer updated';
$_['check_oc_branch_old_why']   = 'Vulnerabilities found in OpenCart 2.x are not officially fixed.';
$_['check_oc_branch_old_fix']   = 'Plan a move to OpenCart / ocStore 3.0. Until then, watch your modules carefully.';
$_['check_oc_old_title'] = 'Old OpenCart 3.0 version';
$_['check_oc_old_why']   = 'Newer 3.0.x releases fix security issues.';
$_['check_oc_old_fix']   = 'Update to the latest 3.0.x after making a backup.';
$_['check_no_https_title'] = 'The store works without HTTPS';
$_['check_no_https_why']   = 'Passwords and customer data travel unencrypted, and browsers mark the site «Not secure».';
$_['check_no_https_fix']   = 'Enable a free SSL certificate in your hosting and set https:// in config.php and admin/config.php.';
$_['check_admin_username_title'] = 'Administrator with the login «admin»';
$_['check_admin_username_why']   = 'Bots try «admin» first — only the password remains to be guessed.';
$_['check_admin_username_fix']   = 'Create an administrator with a different login and disable or delete «admin».';
$_['check_admin_recent_title'] = 'New administrators in the last 30 days';
$_['check_admin_recent_why']   = 'After a hack, attackers often create an administrator for themselves.';
$_['check_admin_recent_fix']   = 'Check that you created these users. Delete unknown ones and change all passwords.';
$_['check_admin_many_title'] = 'Many users with full permissions';
$_['check_admin_many_why']   = 'Every account with full permissions is one more way in.';
$_['check_admin_many_fix']   = 'Keep full permissions only for people who really need them.';
$_['check_core_unknown_title'] = 'The OpenCart version could not be compared with the original';
$_['check_core_unknown_why']   = 'There are no reference files for your version or build, so core changes were checked by signatures only.';
$_['check_core_unknown_fix']   = 'Nothing to do. Post your version in the support topic and we will add it.';
$_['check_core_infected_title'] = 'An OpenCart core file was modified and contains malicious code';
$_['check_core_infected_why']   = 'Attackers hide backdoors in standard files so nobody notices them.';
$_['check_core_infected_fix']   = 'Replace the file with the original from your OpenCart / ocStore archive. Change admin, FTP and database passwords.';
$_['check_core_modified_title'] = 'Modified OpenCart core files';
$_['check_core_modified_why']   = 'These files differ from the original. They may be your or a module’s edits, but malware hides the same way.';
$_['check_core_modified_fix']   = 'Review the list. If neither you nor your developer made these edits, replace the files with originals.';
$_['check_php_in_uploads_title'] = 'PHP file in the images or uploads folder';
$_['check_php_in_uploads_why']   = 'These folders should never contain code. Most often it is a web shell uploaded by an attacker.';
$_['check_php_in_uploads_fix']   = 'Delete the file, change passwords and check which module allowed the upload.';
$_['check_hidden_php_title'] = 'Hidden PHP file';
$_['check_hidden_php_why']   = 'Files starting with a dot are not shown in a normal listing — shells hide this way.';
$_['check_hidden_php_fix']   = 'Open the file. If you did not create it, delete it.';
$_['check_double_extension_title'] = 'File with a double extension (.php.jpg)';
$_['check_double_extension_why']   = 'A shell disguised as an image; some servers execute it as PHP.';
$_['check_double_extension_fix']   = 'Delete the file and check the file upload forms on your site.';
$_['check_php_disguised_title'] = 'PHP code inside an «image»';
$_['check_php_disguised_why']   = 'The file has an image extension but contains PHP code — a classic way to hide a shell.';
$_['check_php_disguised_fix']   = 'Delete the file. Check .htaccess for rules that allow PHP in images.';
$_['check_svg_script_title'] = 'SVG with an embedded script';
$_['check_svg_script_why']   = 'An SVG image with a script can run JavaScript in the visitor’s browser.';
$_['check_svg_script_fix']   = 'If you did not add such an SVG on purpose, replace it with a plain image.';
$_['check_db_tool_title'] = 'Database tool in the site root';
$_['check_db_tool_why']   = 'Adminer, Dumper and similar files give full database access to anyone who knows the URL.';
$_['check_db_tool_fix']   = 'Delete such files right after use.';
$_['check_phpinfo_file_title'] = 'phpinfo() file in the site root';
$_['check_phpinfo_file_why']   = 'Shows software versions, paths and server settings to everyone — a hint for attackers.';
$_['check_phpinfo_file_fix']   = 'Delete the file.';
$_['check_nulled_module_title'] = 'Signs of a «nulled» module';
$_['check_nulled_module_why']   = 'Modules from warez sites often contain backdoors and never get updates.';
$_['check_nulled_module_fix']   = 'Replace the module with a licensed version from the author.';
$_['check_htaccess_malicious_title'] = 'Malicious rules in .htaccess';
$_['check_htaccess_malicious_why']   = 'The rules inject foreign code into every page or allow PHP execution in images.';
$_['check_htaccess_malicious_fix']   = 'Remove these lines from .htaccess and scan the site for shells.';
$_['check_htaccess_suspicious_title'] = 'Suspicious redirects in .htaccess';
$_['check_htaccess_suspicious_why']   = 'Redirects based on search engine or smartphone are a typical way to steal traffic.';
$_['check_htaccess_suspicious_fix']   = 'If you did not set up such redirects, delete the rule.';
$_['check_malware_title'] = 'Malicious code in a file';
$_['check_malware_why']   = 'Signs of a shell, backdoor or miner: the code executes commands received from outside.';
$_['check_malware_fix']   = 'Delete the file or the malicious fragment, change admin, FTP and database passwords, scan again.';
$_['check_suspicious_code_title'] = 'Suspicious obfuscated code';
$_['check_suspicious_code_why']   = 'The code is deliberately obfuscated (base64, hex, chr). Malware does this, and so do protected modules.';
$_['check_suspicious_code_fix']   = 'If the file belongs to a known module, mark it «Not a threat». Otherwise show it to a developer.';
$_['check_mod_malicious_title'] = 'Malicious code in a modification';
$_['check_mod_malicious_why']   = 'Modifications live in the database and inject code into files on refresh. File scanners do not see them.';
$_['check_mod_malicious_fix']   = 'Delete the modification in «Extensions → Modifications» and click «Refresh».';
$_['check_db_malicious_title'] = 'Malicious code in the database';
$_['check_db_malicious_why']   = 'A script in a description or setting runs in every visitor’s browser.';
$_['check_db_malicious_fix']   = 'Open the record and remove the injected code in the editor’s «Source» mode.';
$_['check_db_suspicious_title'] = 'Suspicious script in the database';
$_['check_db_suspicious_why']   = 'An obfuscated script in a description or setting is a common way to hide malicious code.';
$_['check_db_suspicious_fix']   = 'If you added it on purpose (counter, chat), mark it «Not a threat». Otherwise delete it.';
$_['check_front_suspicious_title'] = 'Suspicious script on store pages';
$_['check_front_suspicious_why']   = 'The page contains an obfuscated script.';
$_['check_front_suspicious_fix']   = 'Find where it comes from (module, theme, settings) and check whether you added it.';
$_['check_db_external_script_title'] = 'Script from an unknown site in the database';
$_['check_db_external_script_why']   = 'A script from a foreign domain can steal customer data or alter pages.';
$_['check_db_external_script_fix']   = 'If you did not connect this service, delete the code. If you did (chat, analytics), mark it «Not a threat».';
$_['check_db_hidden_links_title'] = 'Hidden links to other sites';
$_['check_db_hidden_links_why']   = 'Invisible links promote other sites. Google lowers rankings for this.';
$_['check_db_hidden_links_fix']   = 'Delete the hidden block with links in the record editor.';
$_['check_db_spam_links_title'] = 'Spam links in content';
$_['check_db_spam_links_why']   = 'Links to casinos, pharmacies and similar sites hurt your reputation with Google.';
$_['check_db_spam_links_fix']   = 'Delete the links. Check recently added reviews and descriptions.';
$_['check_front_unreachable_title'] = 'The storefront could not be opened from the server';
$_['check_front_unreachable_why']   = 'The server cannot reach itself by its public address, so storefront checks were skipped.';
$_['check_front_unreachable_fix']   = 'Nothing to do. These checks will work on the production domain.';
$_['check_php_errors_visible_title'] = 'PHP errors with file paths are visible on pages';
$_['check_php_errors_visible_why']   = 'Errors reveal server file paths — a hint for attackers — and break page layout.';
$_['check_php_errors_visible_fix']   = 'Turn off «Display errors» in store settings (Server tab) and display_errors in php.ini or .user.ini.';
$_['check_front_malicious_title'] = 'Malicious code on store pages';
$_['check_front_malicious_why']   = 'The code runs in every visitor’s browser: redirects, ads, data theft.';
$_['check_front_malicious_fix']   = 'Find the source: theme, module or database record (see other findings). Remove the code.';
$_['check_front_unknown_scripts_title'] = 'Scripts from unknown domains on the storefront';
$_['check_front_unknown_scripts_why']   = 'Every third-party script has full access to the page, including forms.';
$_['check_front_unknown_scripts_fix']   = 'Review the list. Mark known services (chat, analytics) «Not a threat», remove unknown ones.';
$_['check_security_headers_title'] = 'Basic security headers are missing';
$_['check_security_headers_why']   = 'X-Frame-Options and X-Content-Type-Options protect against framing and some attacks.';
$_['check_security_headers_fix']   = 'Add to .htaccess: Header set X-Frame-Options "SAMEORIGIN" and Header set X-Content-Type-Options "nosniff".';
$_['check_cloaking_redirect_title'] = 'Google is redirected to another site';
$_['check_cloaking_redirect_why']   = 'Googlebot gets a different page than you do. The site can drop out of search.';
$_['check_cloaking_redirect_fix']   = 'Look for the rule in .htaccess and index.php. After cleanup, check the site in Google Search Console.';
$_['check_cloaking_title'] = 'Google sees different content (cloaking)';
$_['check_cloaking_why']   = 'Googlebot gets spam words or links that you do not see. A classic SEO hack.';
$_['check_cloaking_fix']   = 'Look for code checking the User-Agent in the theme, index.php and modifications. After cleanup, ask Google to reindex the pages.';
$_['check_spam_on_page_title'] = 'Spam words on the home page';
$_['check_spam_on_page_why']   = 'Such words usually appear on a store after a hack.';
$_['check_spam_on_page_fix']   = 'Find where they are output (module, banner, description) and remove them.';
$_['check_mobile_redirect_title'] = 'Smartphone visitors are redirected to another site';
$_['check_mobile_redirect_why']   = 'Malware steals mobile traffic, and you do not see it from your computer.';
$_['check_mobile_redirect_fix']   = 'Check .htaccess and index.php for User-Agent rules. Remove them and change passwords.';
$_['check_checkout_malicious_title'] = 'Suspicious code on the checkout page';
$_['check_checkout_malicious_why']   = 'A checkout skimmer steals card details and phone numbers.';
$_['check_checkout_malicious_fix']   = 'Find and remove the code urgently. Check the checkout template, payment modules and database records.';
$_['check_checkout_unknown_scripts_title'] = 'Scripts from unknown domains on checkout';
$_['check_checkout_unknown_scripts_why']   = 'Scripts on this page see everything the customer types.';
$_['check_checkout_unknown_scripts_fix']   = 'Keep only scripts of payment and delivery services you trust.';
$_['check_form_external_title'] = 'The checkout form sends data to another site';
$_['check_form_external_why']   = 'Customer data goes to a third-party server.';
$_['check_form_external_fix']   = 'If this is not your payment provider, remove the form and check the theme.';
$_['check_exposed_logs_title'] = 'The error log is publicly accessible';
$_['check_exposed_logs_why']   = 'The log contains file paths, database queries and other details.';
$_['check_exposed_logs_fix']   = 'Deny access to system/storage in .htaccess or move storage outside the web root.';
$_['check_exposed_git_title'] = 'The .git folder is publicly accessible';
$_['check_exposed_git_why']   = 'Anyone can download your site code and sometimes passwords.';
$_['check_exposed_git_fix']   = 'Block access: add RedirectMatch 404 /\.git to .htaccess or remove the folder from the server.';
$_['check_exposed_env_title'] = 'The .env file is publicly accessible';
$_['check_exposed_env_why']   = '.env usually holds passwords and keys.';
$_['check_exposed_env_fix']   = 'Block access or move it outside the web root. Change the passwords it contains.';
$_['check_exposed_backup_title'] = 'Backups can be downloaded from the site';
$_['check_exposed_backup_why']   = 'Anyone who knows the file name gets the whole database with passwords and customer data.';
$_['check_exposed_backup_fix']   = 'Move the archives outside the web root or delete them now. Change passwords.';
$_['check_backup_in_root_title'] = 'Archives and dumps in the site root';
$_['check_backup_in_root_why']   = 'They are not reachable now, but may become reachable after a server configuration change.';
$_['check_backup_in_root_fix']   = 'Keep backups outside the web root.';
$_['check_dir_listing_title'] = 'Directory listing is enabled';
$_['check_dir_listing_why']   = 'Anyone can see the list of files in your folders.';
$_['check_dir_listing_fix']   = 'Add Options -Indexes to .htaccess.';
$_['check_no_https_redirect_title'] = 'The site opens over http:// without a redirect to https://';
$_['check_no_https_redirect_why']   = 'Some visitors and search engines use the insecure version, and Google sees duplicates.';
$_['check_no_https_redirect_fix']   = 'Add a 301 redirect from http to https in .htaccess.';

$_['check_no_404_title'] = 'Non-existent pages return 200 instead of 404';
$_['check_no_404_why']   = 'Google indexes «empty» pages as real ones — duplicates appear.';
$_['check_no_404_fix']   = 'Check SEO URL modules and the 404 page template: it must return status 404.';
$_['check_no_robots_title'] = 'No robots.txt file';
$_['check_no_robots_why']   = 'Search engines index service pages: cart, search, filters.';
$_['check_no_robots_fix']   = 'Create robots.txt in the site root with OpenCart rules and a link to the sitemap.';
$_['check_robots_block_all_title'] = 'robots.txt blocks the whole site';
$_['check_robots_block_all_why']   = '«Disallow: /» removes the store from search.';
$_['check_robots_block_all_fix']   = 'Remove «Disallow: /» for User-agent: * unless this is a test site.';
$_['check_no_sitemap_title'] = 'No sitemap found';
$_['check_no_sitemap_why']   = 'Without a sitemap Google finds new products more slowly.';
$_['check_no_sitemap_fix']   = 'Enable a sitemap (Google Sitemap or a module) and add it to robots.txt and Google Search Console.';
$_['check_sitemap_not_in_robots_title'] = 'The sitemap is not listed in robots.txt';
$_['check_sitemap_not_in_robots_why']   = 'Search engines find the sitemap faster when it is listed in robots.txt.';
$_['check_sitemap_not_in_robots_fix']   = 'Add «Sitemap: https://your-site/sitemap.xml» to robots.txt.';
$_['check_seo_url_off_title'] = 'SEO URLs are disabled';
$_['check_seo_url_off_why']   = 'URLs like index.php?route=product/product&product_id=42 rank worse and look untrustworthy.';
$_['check_seo_url_off_fix']   = 'Enable «Use SEO URLs» in store settings and rename htaccess.txt to .htaccess.';
$_['check_seo_url_missing_title'] = 'Pages without SEO URLs';
$_['check_seo_url_missing_why']   = 'These products and categories open via technical URLs.';
$_['check_seo_url_missing_fix']   = 'Fill SEO URLs manually or automatically (autofill is in KRIATIZ Multilang SEO).';
$_['check_seo_url_bad_chars_title'] = 'Cyrillic, spaces or capitals in SEO URLs';
$_['check_seo_url_bad_chars_why']   = 'Such URLs turn into unreadable %D0%BA…, break when copied and index worse.';
$_['check_seo_url_bad_chars_fix']   = 'Replace them with Latin characters and 301 redirects from old URLs (available in KRIATIZ Multilang SEO).';
$_['check_seo_url_duplicate_title'] = 'Identical SEO URLs on different pages';
$_['check_seo_url_duplicate_why']   = 'Only one of the pages opens; the other returns 404 or someone else’s content.';
$_['check_seo_url_duplicate_fix']   = 'Make the URLs unique.';
$_['check_meta_title_empty_title'] = 'Empty meta title';
$_['check_meta_title_empty_why']   = 'Google invents its own title in search results, usually a worse one.';
$_['check_meta_title_empty_fix']   = 'Fill meta titles: product name + key benefit + store name.';
$_['check_meta_title_duplicate_title'] = 'Identical meta titles on different pages';
$_['check_meta_title_duplicate_why']   = 'Google treats such pages as duplicates and shows only one.';
$_['check_meta_title_duplicate_fix']   = 'Make the titles unique.';
$_['check_meta_description_empty_title'] = 'Empty meta description';
$_['check_meta_description_empty_why']   = 'Google will use a random piece of the page in search results.';
$_['check_meta_description_empty_fix']   = 'Write a short description with a call to action (up to 160 characters).';
$_['check_description_empty_title'] = 'Products without a description';
$_['check_description_empty_why']   = 'Pages without text rank poorly, and shoppers leave for competitors.';
$_['check_description_empty_fix']   = 'Add descriptions at least to popular products.';
$_['check_store_meta_empty_title'] = 'Home page meta tags are empty';
$_['check_store_meta_empty_why']   = 'The home page is the most important page for search.';
$_['check_store_meta_empty_fix']   = 'Fill «Meta Title» and «Meta Tag Description» in store settings.';

$_['check_lang_no_uk_title'] = 'The site has no Ukrainian language';
$_['check_lang_no_uk_why']   = 'Ukrainian law requires a Ukrainian version for online stores registered in Ukraine.';
$_['check_lang_no_uk_fix']   = 'Add Ukrainian and make it the default language.';
$_['check_lang_default_not_uk_title'] = 'The site does not open in Ukrainian';
$_['check_lang_default_not_uk_why']   = 'Under Article 27 of the language law, the Ukrainian version must open by default.';
$_['check_lang_default_not_uk_fix']   = 'Make Ukrainian the default language (Settings → Localisation). Give other languages separate URLs to keep their rankings.';
$_['check_lang_pack_russian_title'] = 'The Ukrainian storefront language pack is actually Russian';
$_['check_lang_pack_russian_why']   = 'Buttons and menus of the «Ukrainian» version show Russian text.';
$_['check_lang_pack_russian_fix']   = 'Install a full Ukrainian storefront translation or translate catalog/language/uk-ua.';
$_['check_lang_untranslated_title'] = 'Products or categories not translated into Ukrainian';
$_['check_lang_untranslated_why']   = 'The Ukrainian version shows Russian text — this violates the law and hurts Ukrainian SEO.';
$_['check_lang_untranslated_fix']   = 'Translate names and descriptions. KRIATIZ Multilang SEO includes an untranslated text scanner.';
$_['check_lang_no_hreflang_title'] = 'No hreflang for language versions';
$_['check_lang_no_hreflang_why']   = 'Google does not know the pages are translations of each other and shows the wrong language.';
$_['check_lang_no_hreflang_fix']   = 'Add hreflang and x-default tags to all pages (KRIATIZ Multilang SEO does this).';
$_['check_lang_one_url_title'] = 'All languages share one URL';
$_['check_lang_one_url_why']   = 'The language is stored in a cookie, and Google has no cookies — it sees and indexes only one language.';
$_['check_lang_one_url_fix']   = 'Give each language its own URL: site.ua/ (Ukrainian), site.ua/ru/, site.ua/en/.';

$_['text_help'] = '
<h4>What the module does</h4>
<p>KRIATIZ Audit checks your store in 7 stages: server settings, core comparison with original OpenCart / ocStore, all site files, the database, the storefront as a visitor, Googlebot and a smartphone see it, SEO, and language law.</p>
<p><b>The module only reads.</b> It changes and deletes nothing, sends no data to other servers and does not slow the site for visitors: scans run only in the admin panel when you click the button.</p>
<h4>Finding levels</h4>
<ul>
<li><b>Critical</b> — a likely hack or exposed data. Fix first.</li>
<li><b>Recommended</b> — a weak spot or a mistake that hurts sales or SEO.</li>
<li><b>For info</b> — good to know, not urgent.</li>
</ul>
<h4>What «Not a threat» means</h4>
<p>Some modules obfuscate their code or load third-party scripts on purpose. If you are sure a finding is safe, mark it — it will stop affecting the score. Restore it on the «Exclusions» tab.</p>
<h4>If malicious code is found</h4>
<ol>
<li>Back up files and the database.</li>
<li>Remove malicious files and fragments following the hints in each finding.</li>
<li>Change passwords: store administrators, FTP / hosting, database.</li>
<li>Update modules and the theme the hack may have come through.</li>
<li>Run the scan again.</li>
</ol>
<h4>Alongside other security modules</h4>
<p>The audit does not conflict with protection modules (WAF, IP blocking, OcBox Antihack). Protection locks the door against future attacks; the audit answers «have I already been hacked?» and shows what hurts sales.</p>
<h4>Support</h4>
<p>Click «Report to file» and attach it to your message in the module support topic — we will help faster.</p>
';
