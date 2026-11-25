# KRIATIZ Audit — free malware, hack and SEO scanner for OpenCart & ocStore

Answers the question **“has my store already been hacked?”** in a few minutes. Free, no ionCube, no registration, no calls to any external server. The module **only reads** — it changes nothing on your site and shows what it found, why it matters and how to fix it.

[Українською](docs/README-UA.txt) · [По-русски](docs/README-RU.txt) · [Full English guide](docs/README-EN.txt)

**Demo:** https://demoaudit.kriatiz.com/admin/ — login `demo`, password `demo` (read-only). The demo store contains safe test “infections” so you can see how they are detected.

## What it checks (7 stages, 1–5 minutes)

1. **Settings** — install folder, `config.php` permissions, `/admin` path, PHP and OpenCart versions, HTTPS, the `admin` login, new administrators.
2. **OpenCart core** — every core file is compared with the original of your version (checksums for 14 OpenCart and ocStore builds). A modified core file shows up even when the malware is new and unknown.
3. **All site files** — web shells, backdoors, miners, PHP in image folders, `.php.jpg`, PHP inside “images”, SVG with scripts, nulled modules, malicious `.htaccess` rules, Adminer / phpinfo / dumps in the root.
4. **Database** — malicious code in product, category and article descriptions, settings, banners, modules, modifications and reviews; hidden and spam links.
5. **Storefront as a visitor, Googlebot and a smartphone see it** — cloaking, mobile redirects, checkout skimmers, third-party scripts, exposed `.git` / `.env` / logs / backups, directory listing, PHP errors, security headers.
6. **SEO** — SEO URLs, empty and duplicate meta titles and descriptions, products without descriptions, `robots.txt`, sitemap, proper 404, HTTPS redirect.
7. **Language** — Ukrainian present and opened by default, a real Ukrainian storefront language pack, untranslated products, hreflang, separate URLs per language.

**Result:** an overall score 0–100 plus Security, SEO and Language scores. Each finding has a severity (critical / recommended / info), an explanation and the file, line or code fragment. Safe findings can be marked **Not a threat**. The report can be saved to a file.

## Compatibility

- OpenCart 2.3.x.x and 3.x.x.x, ocStore 2.3.x.x and 3.x.x.x — one archive for all versions
- Core checksums: OpenCart 2.3.0.2, 3.0.2.0, 3.0.3.2, 3.0.3.6–3.0.4.1; ocStore 2.3.0.2, 2.3.x, 3.0.2.0, 3.0.3.7, 3.0.4.1
- PHP 7.1+ (tested on 7.4, 8.1, 8.2)
- Any theme: the module works in the admin panel only
- No OCMOD modifications, no site file changes, no ionCube

## Installation

Download `kriatiz_audit.ocmod.zip` from the [latest release](../../releases/latest).

**OpenCart / ocStore 3.x**
1. Admin → Extensions → Installer → upload `kriatiz_audit.ocmod.zip`.
2. Extensions → Extensions → Modules → **KRIATIZ Audit** → Install → Edit.
3. Click **Scan store**.

**OpenCart / ocStore 2.3 without FTP:** upload the contents of the `upload/` folder to the site root, then do steps 2–3.

## Repository layout

```
upload/admin/...                     controller, model, view, language files (en-gb, ru-ru, uk-ua)
upload/system/library/kriatiz/audit/  scanner, security and SEO checks, signatures, core checksums
docs/                                 original install guides in Ukrainian, Russian and English
```

`signatures.dat` stores the detection rules base64-encoded so that the scanner (and other scanners) do not flag the rule file itself as malware.

## Important

The audit finds known signs of hacking and configuration mistakes. It does not guarantee the absence of all malicious code and does not replace backups.

## License

[GPL-3.0](LICENSE) — the same license as OpenCart itself. You may use, modify and redistribute the module; modified versions must stay under GPL-3.0.

## Author and support

Made by **Vladyslav Koshelyev — [KRIATIZ](https://kriatiz.com/en/services/opencart-development/)**, OpenCart and AI developer.

- Store already hacked, or need help with OpenCart? [OpenCart development, cleanup and SEO](https://kriatiz.com/en/services/opencart-development/)
- Support topic and other modules: [OpenCart Forum profile](https://opencartforum.com/profile/697033-gunner91/)
- Multilingual SEO for OpenCart: [KRIATIZ Multilang SEO](https://opencartforum.com/en/files/file/9789-kriatiz-multilang-seo-%E2%80%94-language-urls-ru-en-hreflang-ukrainian-by-default/)
