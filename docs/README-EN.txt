KRIATIZ Audit 1.0.0 (free)
Malware and hack detection, SEO audit and Ukrainian language law check
for OpenCart / ocStore 2.3 and 3.x
Developer: KRIATIZ — https://opencartforum.com/profile/697033-gunner91/

==================================================================
COMPATIBILITY
==================================================================
- OpenCart 2.3.x.x, 3.x.x.x; ocStore 2.3.x.x, 3.x.x.x
- One archive for all versions
- PHP 7.1 or newer (tested on PHP 7.4, 8.1, 8.2)
- Any theme: the module works in the admin panel only
- Changes no site files and adds no modifications (OCMOD)
- No ionCube. The module never contacts the developer's server
  and sends no data about your store anywhere.

==================================================================
INSTALLATION (2 minutes)
==================================================================
OpenCart / ocStore 3.x:
1. Admin → Extensions → Installer → Upload kriatiz_audit.ocmod.zip.
2. Extensions → Extensions → type «Modules» → KRIATIZ Audit →
   «Install» (green button), then «Edit».
3. Click «Scan store».

OpenCart / ocStore 2.3: the 2.3 installer copies files via FTP only.
If FTP is not configured, upload the contents of the upload/ folder
from kriatiz_audit.ocmod.zip to the site root (FTP or the hosting file
manager), then do steps 2–3.

No need to refresh modifications.

==================================================================
WHAT IT CHECKS (7 stages, 1–5 minutes)
==================================================================
1. Settings: install folder, config.php permissions, /admin path,
   PHP and OpenCart versions, HTTPS, «admin» login, new administrators.
2. OpenCart core: every core file is compared with the original of your
   version (checksums for 14 OpenCart and ocStore builds).
3. All site files: web shells, backdoors, miners, PHP in image folders,
   .php.jpg files, PHP inside «images», SVG with scripts, nulled modules,
   malicious .htaccess rules, Adminer / phpinfo / dumps in the root.
4. Database: malicious code in product, category and article
   descriptions, settings, banners, modules, modifications and reviews;
   hidden links and spam links.
5. Storefront as a visitor, Googlebot and a smartphone see it:
   cloaking, mobile redirects, checkout skimmers, third-party scripts,
   exposed .git / .env / logs / backups, directory listing, PHP errors,
   security headers.
6. SEO: SEO URLs, empty and duplicate meta titles / descriptions,
   product descriptions, robots.txt, sitemap, proper 404, https redirect.
7. Language and law: Ukrainian present, opened by default, real
   Ukrainian storefront language pack, untranslated products, hreflang,
   separate URLs per language.

Result: an overall score 0–100 plus Security, SEO and Language scores.
Each finding explains why it matters and how to fix it.

==================================================================
IMPORTANT
==================================================================
- The module ONLY READS. It deletes and changes nothing.
- Scans run only from the admin button and do not slow down the store.
- Some modules obfuscate their code or load third-party scripts on
  purpose (chats, analytics). If a finding is safe, mark it
  «Not a threat» and it will stop affecting the score.
- The audit finds known signs of hacking. It does not guarantee the
  absence of all malicious code and does not replace backups.

==================================================================
UNINSTALL
==================================================================
Extensions → Modules → KRIATIZ Audit → «Uninstall». Module tables
(kriatiz_audit_run, kriatiz_audit_issue, kriatiz_audit_ignore) are
dropped. Module files can be removed manually:
admin/controller|model|language|view/.../kriatiz_audit*,
system/library/kriatiz/audit/.

==================================================================
SUPPORT
==================================================================
Module support topic on opencartforum.com. Click «Report to file» in
the module and attach it to your message — we will help faster.
