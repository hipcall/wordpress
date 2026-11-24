# Developer Guide

Internal guide for people working on the Hipcall WordPress plugin. It lives on GitHub only: it is listed in `.distignore`, so it never reaches WordPress.org. The user-facing docs are [README.md](README.md) and [readme.txt](readme.txt).

## Where the code goes

This repository feeds two places:

| Destination | What goes there | When |
| --- | --- | --- |
| GitHub (`hipcall/wordpress`) | Everything | Every push |
| WordPress.org SVN (`plugins.svn.wordpress.org/hipcall`) | Only the files **not** listed in [.distignore](.distignore), plus the images in `.wordpress-org/` | Through GitHub Actions only |

Nobody needs to use SVN by hand. If you add a file that is only for developers (docs, configs, tooling, tests), **add it to `.distignore`** or it will be shipped to every WordPress site that installs the plugin.

## GitHub Actions

| Workflow | Trigger | What it does |
| --- | --- | --- |
| [ci.yml](.github/workflows/ci.yml) | Every PR and push to `main` | PHP lint, WordPress Coding Standards, Plugin Check, PHPUnit, Jest |
| [deploy.yml](.github/workflows/deploy.yml) | A GitHub Release is **published** | Checks that the tag matches the version in the files, copies the plugin to SVN `trunk/` and `tags/X.Y.Z/`, uploads `.wordpress-org/` images, attaches `hipcall.zip` to the release |
| [wporg-assets.yml](.github/workflows/wporg-assets.yml) | Push to `main` that changes `readme.txt` or `.wordpress-org/**` (or manual run) | Updates only `readme.txt` and images on WordPress.org, without a new version |

Both SVN workflows use the repository secrets `SVN_USERNAME` (`hipcall`) and `SVN_PASSWORD` (the SVN password from https://profiles.wordpress.org/me/profile/edit/group/3/?screen=svn-password). If the password is regenerated there, update the secret in **Settings › Secrets and variables › Actions**.

## Day-to-day rules

1. **Work in branches and open PRs into `main`.** CI must be green before merging.
2. **Do not change version numbers by hand.** The version lives in four places (`Version:` header and `HIPCALL_VERSION` in `hipcall.php`, `Stable tag:` in `readme.txt`, `Project-Id-Version` in `languages/hipcall.pot`). `bin/release.sh` updates all of them.
3. **`readme.txt` on `main` is live.** Merging a change to `readme.txt` publishes it on wordpress.org/plugins/hipcall within minutes (via `wporg-assets.yml`). Only merge text that is true for the version that is already released.
4. **Do not add changelog entries to `readme.txt` in feature PRs.** They would show up publicly before the version exists. Describe the user-facing change in the PR title and description instead; the release script collects the notes at release time.
5. **Use clear commit and PR titles.** The release script uses the commit subjects since the last release as the changelog draft.
6. **Images for the plugin page** go in `.wordpress-org/` with the WordPress.org file names: `banner-772x250.png`, `banner-1544x500.png`, `icon-128x128.png`, `icon-256x256.png`, `icon.svg`, `screenshot-N.png`. Screenshot captions are the `== Screenshots ==` list in `readme.txt` (line N = `screenshot-N.png`).
7. **Follow the plugin guidelines**: GPL-compatible code only, no tracking or external calls without user consent, escape all output, sanitize all input, text domain `hipcall`. Run Plugin Check / PHPCS (CI does this on every PR).

## Running tests locally

See [Running Tests in README.md](README.md#running-tests). Short version:

```bash
composer update && bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest && vendor/bin/phpunit
npm ci && npm test
```

## Releasing a new version

Requirements: you are on `main` with a clean working tree, and the [GitHub CLI](https://cli.github.com/) is installed and logged in (`brew install gh && gh auth login`).

```bash
bin/release.sh
```

The script:

1. Fetches `origin`, makes sure your `main` is up to date, and checks that the four version numbers agree.
2. Asks for the new version: patch (bug fixes), minor (new features) or major (breaking changes). `current` releases the version already in the files without a bump (only offered when it has not been released yet, e.g. the first release or a retry).
3. Opens your `$EDITOR` (`nano` if it is not set; use e.g. `EDITOR="code --wait" bin/release.sh` for VS Code) with a changelog draft built from the commit messages. Rewrite it for site owners: it is shown publicly on the plugin page. If `readme.txt` already has a `= X.Y.Z =` entry, that entry is used instead.
4. Updates the four version numbers, adds the changelog entry to `readme.txt`, and shows the diff.
5. After you confirm: commits `chore(release): X.Y.Z`, pushes `main`, and publishes the GitHub Release `X.Y.Z`. That triggers `deploy.yml`.
6. Offers to follow the deploy run in the terminal.

Non-interactive form (also what Claude Code uses):

```bash
bin/release.sh minor --notes-file /path/to/notes.txt --dry-run   # preview, changes are reverted
bin/release.sh minor --notes-file /path/to/notes.txt --yes       # release
```

`--notes-file` holds one changelog item per line (`* Fix: ...`). The version argument can be `patch`, `minor`, `major`, `current` or an exact `X.Y.Z`.

Tags are plain `X.Y.Z` (no `v` prefix). Pushing a tag by itself does **not** deploy, and a draft release does not either. Only a published release does, which is why releases should always go through the script.

### After a release

- Check that the **Deploy to WordPress.org** run is green: https://github.com/hipcall/wordpress/actions/workflows/deploy.yml
- Check SVN: https://plugins.trac.wordpress.org/browser/hipcall (`trunk/`, `tags/X.Y.Z/`, `assets/`)
- Check the plugin page: https://wordpress.org/plugins/hipcall/ (can take a few minutes; search results can take longer)

## Troubleshooting

**Deploy failed with "Version mismatch".** Nothing was sent to SVN. Delete the release and tag, fix the files on `main`, and run the script again:

```bash
gh release delete X.Y.Z --repo hipcall/wordpress --cleanup-tag --yes
git fetch --prune --prune-tags
bin/release.sh current
```

**Deploy failed with an SVN authentication error.** Check the `SVN_USERNAME` / `SVN_PASSWORD` secrets (see above), then re-run the failed job from the Actions page. No new release is needed.

**Deploy failed after `tags/X.Y.Z` was created in SVN.** A version cannot be re-used on WordPress.org. Fix the problem and release the next patch version.

**`wporg-assets.yml` says "Stable tag has not been released yet".** `readme.txt` on `main` points to a version that has no tag. Release it with `bin/release.sh current`, or fix the `Stable tag` line.

**`wporg-assets.yml` failed with "Other files have been modified".** It should not happen because it runs with `IGNORE_OTHER_FILES`. If it does, re-run it from the Actions page; it is safe.
