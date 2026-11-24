# Hipcall WordPress Plugin

WordPress plugin (slug `hipcall`) that embeds the Hipcall Webchat widget and Marketing Tracker, with HMAC-SHA256 identity verification. Published on https://wordpress.org/plugins/hipcall/. PHP 7.4+, WordPress 6.3+.

The full developer guide is imported below. Read it before working on releases, workflows, `readme.txt` or `.wordpress-org/`.

@DEVELOPMENT.md

## Rules for Claude Code

- **Never edit version numbers by hand** (`Version:` and `HIPCALL_VERSION` in `hipcall.php`, `Stable tag:` in `readme.txt`, `Project-Id-Version` in `languages/hipcall.pot`). Only `bin/release.sh` changes them.
- **Never add a changelog entry for an unreleased version to `readme.txt`** in a feature branch. `readme.txt` on `main` goes live on WordPress.org immediately. Put user-facing change notes in the PR description.
- **Never use `svn`, push tags, or create GitHub releases directly.** Releases go through `bin/release.sh` only, and only when the user explicitly asks for a release.
- When the user asks for a release:
  1. Make sure you are on `main`, the tree is clean, and the work to release is merged.
  2. Read the commits since the last tag (`git log --no-merges $(git describe --tags --abbrev=0)..HEAD`) and the merged PR descriptions (`gh pr list --state merged`), and pick patch / minor / major (semver: fixes / features / breaking).
  3. Write the changelog to a file in your scratchpad: one line per item, starting with `* `, written for site owners (not developers), matching the style of the existing `== Changelog ==` entries in `readme.txt`. Show it to the user with the chosen version and get their approval.
  4. Run `bin/release.sh <bump> --notes-file <file> --dry-run`, check the diff, then `bin/release.sh <bump> --notes-file <file> --yes`.
  5. Follow the deploy with `gh run watch` (`gh run list --workflow deploy.yml --limit 1`) and report the result.
  The script is interactive without `--yes`; you cannot answer its prompts, so always pass the version and `--notes-file`.
- Any new developer-only file or folder (docs, configs, tooling) must be added to `.distignore` so it is not shipped to WordPress.org.
- Follow WordPress Coding Standards (`phpcs.xml.dist`): escape output, sanitize input, nonces/capability checks in admin, text domain `hipcall`, prefix everything with `hipcall_` / `Hipcall_`.
- Add or update tests in `tests/phpunit` (PHP) and `tests/js` (Jest) with every behavior change. See "Running Tests" in `README.md`.
- If a user-visible string changes, update `languages/hipcall.pot` too.
