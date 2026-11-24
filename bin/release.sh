#!/usr/bin/env bash
#
# Hipcall WordPress plugin release manager.
#
# Bumps the version everywhere it lives, writes the readme.txt changelog entry,
# commits, pushes to main and publishes a GitHub Release. Publishing the release
# triggers .github/workflows/deploy.yml, which ships the version to WordPress.org SVN.
#
# Usage:
#   bin/release.sh                          Interactive: pick the version, edit the changelog.
#   bin/release.sh minor                    Bump type: patch | minor | major
#   bin/release.sh 1.4.0                    Exact version
#   bin/release.sh current                  Release the version already in the files (no bump),
#                                           e.g. the very first release or a retry.
#
# Options:
#   --notes-file FILE   Changelog lines for readme.txt (one "* ..." item per line).
#                       Without it the script opens $EDITOR (default: nano) with a draft
#                       built from commits.
#   --yes, -y           Do not ask for confirmation (needs a version and, unless the
#                       changelog entry already exists, --notes-file).
#   --dry-run           Make the changes, show the diff, then revert. Nothing is committed.
#   --help, -h          Show this help.
#
# Works with the stock macOS bash 3.2 and BSD sed/grep.

set -euo pipefail

REPO="hipcall/wordpress"
PLUGIN_FILE="hipcall.php"
README="readme.txt"
POT="languages/hipcall.pot"

if [ -t 1 ]; then
	RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; BLUE=$'\033[0;34m'; BOLD=$'\033[1m'; NC=$'\033[0m'
else
	RED=""; GREEN=""; YELLOW=""; BLUE=""; BOLD=""; NC=""
fi

info() { printf '%s%s%s\n' "$BLUE" "$*" "$NC"; }
ok()   { printf '%s%s%s\n' "$GREEN" "$*" "$NC"; }
warn() { printf '%s%s%s\n' "$YELLOW" "$*" "$NC"; }
die()  { printf '%sError: %s%s\n' "$RED" "$*" "$NC" >&2; exit 1; }

usage() { awk 'NR > 2 && !/^#/ { exit } NR > 2 { sub(/^# ?/, ""); print }' "$0"; }

# ---------------------------------------------------------------------------
# Arguments
# ---------------------------------------------------------------------------

BUMP=""
NOTES_FILE=""
ASSUME_YES=0
DRY_RUN=0

while [ $# -gt 0 ]; do
	case "$1" in
		patch|minor|major|current) BUMP="$1" ;;
		[0-9]*) BUMP="$1" ;;
		--notes-file)
			[ $# -ge 2 ] || die "--notes-file needs a path"
			NOTES_FILE="$2"; shift ;;
		--notes-file=*) NOTES_FILE="${1#*=}" ;;
		-y|--yes) ASSUME_YES=1 ;;
		--dry-run) DRY_RUN=1 ;;
		-h|--help) usage; exit 0 ;;
		*) die "Unknown argument: $1 (see --help)" ;;
	esac
	shift
done

if [ -n "$NOTES_FILE" ]; then
	[ -f "$NOTES_FILE" ] || die "Notes file not found: $NOTES_FILE"
	NOTES_FILE="$(cd "$(dirname "$NOTES_FILE")" && pwd)/$(basename "$NOTES_FILE")"
fi

if [ $ASSUME_YES -eq 0 ] && [ ! -t 0 ]; then
	die "No terminal for prompts. Pass a version, --notes-file and --yes (see --help)."
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

is_semver() { [[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; }

# version_gt A B: true when A > B (both X.Y.Z).
version_gt() {
	local IFS=.
	local a=($1) b=($2) i
	for i in 0 1 2; do
		if ((10#${a[i]} > 10#${b[i]})); then return 0; fi
		if ((10#${a[i]} < 10#${b[i]})); then return 1; fi
	done
	return 1
}

confirm() {
	[ $ASSUME_YES -eq 1 ] && return 0
	local answer
	read -r -p "$1 [y/N] " answer || return 1
	[ "$answer" = "y" ] || [ "$answer" = "Y" ]
}

read_versions() {
	V_HEADER=$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][0-9.]*).*/\1/p' "$PLUGIN_FILE" | head -1)
	V_CONST=$(sed -nE "s/.*define\([[:space:]]*'HIPCALL_VERSION',[[:space:]]*'([^']+)'.*/\1/p" "$PLUGIN_FILE" | head -1)
	V_STABLE=$(sed -nE 's/^Stable tag:[[:space:]]*([0-9][0-9.]*).*/\1/p' "$README" | head -1)
	V_POT=$(sed -nE 's/^"Project-Id-Version: Hipcall ([0-9.]+)\\n"$/\1/p' "$POT" | head -1)
}

print_versions() {
	printf '  %-40s %s\n' "$PLUGIN_FILE (Version header)" "${V_HEADER:-<missing>}"
	printf '  %-40s %s\n' "$PLUGIN_FILE (HIPCALL_VERSION)" "${V_CONST:-<missing>}"
	printf '  %-40s %s\n' "$README (Stable tag)" "${V_STABLE:-<missing>}"
	printf '  %-40s %s\n' "$POT (Project-Id-Version)" "${V_POT:-<missing>}"
}

tag_exists() { git rev-parse -q --verify "refs/tags/$1" >/dev/null; }

# ---------------------------------------------------------------------------
# Cleanup: revert file edits if we stop before committing.
# ---------------------------------------------------------------------------

MODIFIED=0
COMMITTED=0
TMP_DIR="$(mktemp -d)"

cleanup() {
	local status=$?
	if [ $MODIFIED -eq 1 ] && [ $COMMITTED -eq 0 ]; then
		git checkout -- "$PLUGIN_FILE" "$README" "$POT" 2>/dev/null || true
		rm -f "$PLUGIN_FILE.bak" "$README.bak" "$POT.bak"
		[ $DRY_RUN -eq 1 ] && [ $status -eq 0 ] || warn "File changes reverted."
	fi
	rm -rf "$TMP_DIR"
}
trap cleanup EXIT

# ---------------------------------------------------------------------------
# Preconditions
# ---------------------------------------------------------------------------

printf '%s=== Hipcall WordPress Release ===%s\n\n' "$BOLD" "$NC"

command -v gh >/dev/null 2>&1 || die "GitHub CLI is required. Install it with: brew install gh"
gh auth status >/dev/null 2>&1 || die "GitHub CLI is not logged in. Run: gh auth login"

[ "$(git branch --show-current)" = "main" ] || die "Switch to the main branch first: git checkout main"
[ -z "$(git status --porcelain)" ] || die "Working tree is not clean. Commit or stash your changes first."

info "Fetching origin..."
git fetch origin main --tags --quiet

if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ]; then
	if git merge-base --is-ancestor HEAD origin/main; then
		info "Fast-forwarding main to origin/main..."
		git merge --ff-only --quiet origin/main
	else
		die "Local main has commits that are not on origin/main. Open a PR for them (or reset) first."
	fi
fi

read_versions
if [ -z "$V_HEADER" ] || [ "$V_HEADER" != "$V_CONST" ] || [ "$V_HEADER" != "$V_STABLE" ] || [ "$V_HEADER" != "$V_POT" ]; then
	print_versions
	die "Version numbers in the files do not match each other. Fix them on main first."
fi
CURRENT="$V_HEADER"

LATEST_TAG=$(git tag --list | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -t. -k1,1n -k2,2n -k3,3n | tail -1 || true)

echo
printf '  Version in files: %s%s%s' "$BOLD" "$CURRENT" "$NC"
if tag_exists "$CURRENT"; then echo " (released)"; else printf ' %s(not released yet)%s\n' "$YELLOW" "$NC"; fi
printf '  Latest tag:       %s\n\n' "${LATEST_TAG:-<none>}"

# ---------------------------------------------------------------------------
# Pick the version
# ---------------------------------------------------------------------------

IFS=. read -r MAJOR MINOR PATCH <<<"$CURRENT"
NEXT_PATCH="$MAJOR.$MINOR.$((PATCH + 1))"
NEXT_MINOR="$MAJOR.$((MINOR + 1)).0"
NEXT_MAJOR="$((MAJOR + 1)).0.0"

if [ -z "$BUMP" ]; then
	[ $ASSUME_YES -eq 0 ] || die "--yes needs a version: patch | minor | major | current | X.Y.Z"
	echo "Which version do you want to release?"
	if ! tag_exists "$CURRENT"; then
		echo "  0) Current: $CURRENT   (release the files as they are, no bump)"
	fi
	echo "  1) Patch:   $NEXT_PATCH   (bug fixes only)"
	echo "  2) Minor:   $NEXT_MINOR   (new features, backwards compatible)"
	echo "  3) Major:   $NEXT_MAJOR   (breaking changes)"
	echo "  4) Custom"
	read -r -p "Select: " choice
	case "$choice" in
		0) BUMP="current" ;;
		1) BUMP="patch" ;;
		2) BUMP="minor" ;;
		3) BUMP="major" ;;
		4) read -r -p "Version (X.Y.Z, no 'v'): " BUMP ;;
		*) die "Invalid choice" ;;
	esac
fi

case "$BUMP" in
	current) NEW="$CURRENT" ;;
	patch)   NEW="$NEXT_PATCH" ;;
	minor)   NEW="$NEXT_MINOR" ;;
	major)   NEW="$NEXT_MAJOR" ;;
	*)       NEW="${BUMP#v}" ;;
esac

is_semver "$NEW" || die "Invalid version '$NEW'. Use X.Y.Z without a 'v' prefix."
tag_exists "$NEW" && die "Tag $NEW already exists. WordPress.org versions cannot be re-released; pick a higher version."
if gh release view "$NEW" --repo "$REPO" >/dev/null 2>&1; then
	die "A GitHub release named $NEW already exists."
fi
if [ "$NEW" = "$CURRENT" ]; then
	:
elif ! version_gt "$NEW" "$CURRENT"; then
	die "$NEW must be higher than the current version $CURRENT."
fi
if [ -n "$LATEST_TAG" ] && ! version_gt "$NEW" "$LATEST_TAG"; then
	die "$NEW must be higher than the latest released version $LATEST_TAG."
fi

# ---------------------------------------------------------------------------
# Commits since the last release
# ---------------------------------------------------------------------------

if [ -n "$LATEST_TAG" ]; then RANGE="$LATEST_TAG..HEAD"; else RANGE="HEAD"; fi

info "Commits since ${LATEST_TAG:-the beginning}:"
git --no-pager log --no-merges --pretty='  %h %s' $RANGE | head -30 || true
echo

# ---------------------------------------------------------------------------
# Changelog entry in readme.txt
# ---------------------------------------------------------------------------

grep -qE '^== Changelog ==[[:space:]]*$' "$README" || die "No '== Changelog ==' section in $README."

if grep -qxF "= $NEW =" "$README"; then
	ok "$README already has a changelog entry for $NEW, using it."
	[ -z "$NOTES_FILE" ] || warn "Ignoring --notes-file because the entry already exists."
else
	RAW_NOTES="$TMP_DIR/notes.raw"
	if [ -n "$NOTES_FILE" ]; then
		cp "$NOTES_FILE" "$RAW_NOTES"
	else
		[ $ASSUME_YES -eq 0 ] || die "--yes needs --notes-file (no changelog entry for $NEW in $README)."
		{
			echo "# Changelog for $NEW. It is shown publicly on wordpress.org/plugins/hipcall."
			echo "# Write for site owners, not developers: what changed for them and why it matters."
			echo "# One item per line. Lines starting with # are removed. Save an empty file to abort."
			echo "# Draft from commit messages:"
			echo "#"
			git log --no-merges --pretty='* %s' $RANGE | grep -v '^\* chore(release)' || true
		} >"$RAW_NOTES"
		DEFAULT_EDITOR="vi"
		command -v nano >/dev/null 2>&1 && DEFAULT_EDITOR="nano"
		EDITOR_CMD="${VISUAL:-${EDITOR:-$DEFAULT_EDITOR}}"
		info "Opening the changelog draft in: $EDITOR_CMD"
		# Intentionally unquoted so values like "code --wait" work.
		if ! $EDITOR_CMD "$RAW_NOTES"; then
			die "The editor '$EDITOR_CMD' failed. Use another one, e.g. EDITOR=nano bin/release.sh (or EDITOR=\"code --wait\"), or write the notes to a file and pass --notes-file FILE."
		fi
	fi

	ENTRY="$TMP_DIR/entry"
	{
		echo "= $NEW ="
		grep -v '^#' "$RAW_NOTES" \
			| sed -E 's/[[:space:]]+$//' \
			| sed '/^$/d' \
			| sed -E 's/^[-*+][[:space:]]*//; s/^/* /' || true
	} >"$ENTRY"
	[ $(($(wc -l <"$ENTRY"))) -gt 1 ] || die "Changelog is empty. Aborted."

	awk -v entry_file="$ENTRY" '
		{ print }
		!done && /^== Changelog ==[[:space:]]*$/ {
			print ""
			while ((getline line < entry_file) > 0) print line
			done = 1
		}
	' "$README" >"$TMP_DIR/readme.new"
	MODIFIED=1
	cat "$TMP_DIR/readme.new" >"$README"
fi

# ---------------------------------------------------------------------------
# Bump versions
# ---------------------------------------------------------------------------

if [ "$NEW" != "$CURRENT" ]; then
	MODIFIED=1
	sed -i.bak -E "s/^([[:space:]]*\*[[:space:]]*Version:[[:space:]]*)[0-9][0-9.]*/\1$NEW/" "$PLUGIN_FILE"
	sed -i.bak -E "s/(define\([[:space:]]*'HIPCALL_VERSION',[[:space:]]*')[^']+'/\1$NEW'/" "$PLUGIN_FILE"
	sed -i.bak -E "s/^(Stable tag:[[:space:]]*)[0-9][0-9.]*/\1$NEW/" "$README"
	sed -i.bak -E "s/^(\"Project-Id-Version: Hipcall )[0-9.]+/\1$NEW/" "$POT"
	rm -f "$PLUGIN_FILE.bak" "$README.bak" "$POT.bak"
fi

read_versions
if [ "$V_HEADER" != "$NEW" ] || [ "$V_CONST" != "$NEW" ] || [ "$V_STABLE" != "$NEW" ] || [ "$V_POT" != "$NEW" ]; then
	print_versions
	die "Could not update every version number to $NEW."
fi

RELEASE_NOTES="$TMP_DIR/release-notes.md"
awk -v heading="= $NEW =" '
	$0 == heading { found = 1; next }
	found && /^=/ { exit }
	found && NF { print }
' "$README" >"$RELEASE_NOTES"

# ---------------------------------------------------------------------------
# Review
# ---------------------------------------------------------------------------

echo
info "Changes:"
if git diff --quiet; then
	echo "  (none, the files already describe $NEW)"
else
	git --no-pager diff
fi
echo
info "Release notes for $NEW:"
sed 's/^/  /' "$RELEASE_NOTES"
echo

if [ $DRY_RUN -eq 1 ]; then
	ok "Dry run finished. Nothing was committed, pushed or released; file changes were reverted."
	exit 0
fi

confirm "Commit, push to main and publish $NEW to WordPress.org?" || { warn "Cancelled."; exit 1; }

# ---------------------------------------------------------------------------
# Commit, push, release
# ---------------------------------------------------------------------------

if ! git diff --quiet; then
	git add "$PLUGIN_FILE" "$README" "$POT"
	git commit --quiet -m "chore(release): $NEW"
	COMMITTED=1
	ok "Committed: chore(release): $NEW"
fi

info "Pushing main..."
if ! git push --quiet origin HEAD:main; then
	die "Push failed. The release commit is only local. Fix the problem, run 'git push origin main', then: gh release create $NEW --repo $REPO --target main --title $NEW"
fi

info "Publishing GitHub release $NEW..."
if ! gh release create "$NEW" --repo "$REPO" --target "$(git rev-parse HEAD)" --title "$NEW" --notes-file "$RELEASE_NOTES"; then
	die "Main was pushed but the release was not created. Retry with: gh release create $NEW --repo $REPO --target main --title $NEW"
fi

echo
ok "Released $NEW."
echo "  Deploy:  https://github.com/$REPO/actions/workflows/deploy.yml"
echo "  Release: https://github.com/$REPO/releases/tag/$NEW"
echo "  Plugin:  https://wordpress.org/plugins/hipcall/ (can take a few minutes to update)"

if [ $ASSUME_YES -eq 0 ] && confirm "Watch the deploy now?"; then
	sleep 5
	RUN_ID=$(gh run list --repo "$REPO" --workflow deploy.yml --event release --limit 1 --json databaseId --jq '.[0].databaseId' || true)
	if [ -n "$RUN_ID" ]; then
		gh run watch "$RUN_ID" --repo "$REPO" --exit-status
	else
		warn "Deploy run not found yet. Check the Actions link above."
	fi
fi
