#!/bin/bash
# Build install zip for LanceDesk Form Guard (runtime files only).
# Dev paths (docs, Stitch templates, resources, etc.) are excluded.
#
# Works on Linux and Windows (Git Bash / MINGW / MSYS / PowerShell fallback).
# Source folder may be named lancedesk-form-guard or *-main, etc.;
# the zip always uses the WordPress slug folder: lancedesk-form-guard/
#
# Usage (from plugin root):
#   bash zip-plugin.sh
#   ./zip-plugin.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$SCRIPT_DIR"
ZIP_ROOT_NAME="lancedesk-form-guard"
PARENT_DIR="$(dirname "$PLUGIN_ROOT")"
OUT="${PARENT_DIR}/${ZIP_ROOT_NAME}.zip"

is_windows() {
	case "$(uname -s 2>/dev/null || echo unknown)" in
		MINGW*|MSYS*|CYGWIN*|Windows_NT*) return 0 ;;
	esac
	[[ -n "${WINDIR:-}" || -n "${SystemRoot:-}" ]]
}

# Convert a Unix/Git-Bash path to a Windows path for PowerShell.
to_win_path() {
	local p="$1"
	if command -v cygpath >/dev/null 2>&1; then
		cygpath -w "$p"
		return
	fi
	# /c/foo/bar -> C:\foo\bar
	if [[ "$p" =~ ^/([a-zA-Z])/(.*)$ ]]; then
		local drive="${BASH_REMATCH[1]}"
		local rest="${BASH_REMATCH[2]}"
		drive="$(echo "$drive" | tr '[:lower:]' '[:upper:]')"
		printf '%s:\\%s\n' "$drive" "${rest//\//\\}"
		return
	fi
	printf '%s\n' "$p"
}

find_zip_bin() {
	local candidate
	for candidate in \
		"$(command -v zip 2>/dev/null || true)" \
		"/usr/bin/zip" \
		"/mingw64/bin/zip" \
		"/c/Program Files/Git/usr/bin/zip.exe" \
		"/c/Program Files (x86)/Git/usr/bin/zip.exe"
	do
		if [[ -n "$candidate" && -x "$candidate" ]]; then
			printf '%s\n' "$candidate"
			return 0
		fi
	done
	return 1
}

find_unzip_bin() {
	local candidate
	for candidate in \
		"$(command -v unzip 2>/dev/null || true)" \
		"/usr/bin/unzip" \
		"/mingw64/bin/unzip" \
		"/c/Program Files/Git/usr/bin/unzip.exe" \
		"/c/Program Files (x86)/Git/usr/bin/unzip.exe"
	do
		if [[ -n "$candidate" && -x "$candidate" ]]; then
			printf '%s\n' "$candidate"
			return 0
		fi
	done
	return 1
}

find_powershell() {
	local candidate
	for candidate in \
		"$(command -v powershell.exe 2>/dev/null || true)" \
		"$(command -v pwsh.exe 2>/dev/null || true)" \
		"/c/Windows/System32/WindowsPowerShell/v1.0/powershell.exe" \
		"/c/Windows/SysWOW64/WindowsPowerShell/v1.0/powershell.exe"
	do
		if [[ -n "$candidate" && -x "$candidate" ]]; then
			printf '%s\n' "$candidate"
			return 0
		fi
	done
	return 1
}

if [[ ! -f "${PLUGIN_ROOT}/lancedesk-form-guard.php" ]]; then
	LEGACY_ROOT="${PARENT_DIR}/${ZIP_ROOT_NAME}"
	if [[ -f "${LEGACY_ROOT}/lancedesk-form-guard.php" ]]; then
		PLUGIN_ROOT="$LEGACY_ROOT"
	else
		echo "Error: main plugin file not found in:" >&2
		echo "  ${SCRIPT_DIR}" >&2
		echo "  ${LEGACY_ROOT}" >&2
		exit 1
	fi
fi

ZIP_BIN="$(find_zip_bin || true)"
UNZIP_BIN="$(find_unzip_bin || true)"
PS_BIN=""
if is_windows; then
	PS_BIN="$(find_powershell || true)"
fi

if [[ -z "$ZIP_BIN" ]] && [[ -z "$PS_BIN" ]]; then
	echo "Error: no zipper found." >&2
	echo "Install Info-ZIP (zip), or run on Windows where PowerShell is available." >&2
	exit 1
fi

# Stage under the canonical WP folder name so the zip works regardless of
# checkout folder name (e.g. lancedesk-form-guard-main on Windows).
STAGE="$(mktemp -d 2>/dev/null || mktemp -d -t ld-fg-zip)"
cleanup() {
	rm -rf "$STAGE"
}
trap cleanup EXIT

STAGE_PLUGIN="${STAGE}/${ZIP_ROOT_NAME}"
mkdir -p "$STAGE_PLUGIN"

# Copy then prune so zip / PowerShell both ship the same WordPress-only tree.
if command -v rsync >/dev/null 2>&1; then
	rsync -a \
		--exclude '.git/' \
		--exclude '.github/' \
		--exclude '.cursor/' \
		--exclude 'bin/' \
		--exclude 'tests/' \
		--exclude 'docs/' \
		--exclude 'html_templates/' \
		--exclude 'resources/' \
		--exclude 'dist/' \
		--exclude 'node_modules/' \
		--exclude '/vendor/' \
		--exclude '.gitignore' \
		--exclude '.gitattributes' \
		--exclude '.editorconfig' \
		--exclude '.distignore' \
		--exclude '.DS_Store' \
		--exclude 'Thumbs.db' \
		--exclude '*.sh' \
		--exclude '*.md' \
		--exclude 'composer.json' \
		--exclude 'composer.lock' \
		--exclude 'package.json' \
		--exclude 'package-lock.json' \
		--exclude 'tailwind.config.js' \
		--exclude 'admin/css/ld-fg-admin-src.css' \
		--exclude 'phpcs.xml' \
		--exclude 'phpcs.xml.dist' \
		--exclude 'phpunit.xml' \
		--exclude 'phpunit.xml.dist' \
		--exclude '.phpunit.result.cache' \
		"${PLUGIN_ROOT}/" "${STAGE_PLUGIN}/"
else
	if cp -a "${PLUGIN_ROOT}/." "${STAGE_PLUGIN}/" 2>/dev/null; then
		:
	else
		cp -R "${PLUGIN_ROOT}/." "${STAGE_PLUGIN}/"
	fi
fi

# Prune excluded paths from the stage so both zip and PowerShell produce the same package.
prune_stage() {
	local path
	for path in \
		bin tests docs html_templates resources dist \
		node_modules vendor \
		.git .github .cursor
	do
		rm -rf "${STAGE_PLUGIN}/${path}"
	done

	# Dotfiles / tooling files at package root
	local f
	for f in \
		.gitignore .gitattributes .editorconfig .distignore .DS_Store Thumbs.db \
		phpcs.xml phpcs.xml.dist phpunit.xml phpunit.xml.dist .phpunit.result.cache \
		composer.json composer.lock package.json package-lock.json \
		tailwind.config.js \
		README.md CHANGELOG.md LICENSE.md CONTRIBUTING.md \
		zip-plugin.sh repeat.sh
	do
		rm -f "${STAGE_PLUGIN}/${f}"
	done

	# Tailwind source (compiled CSS is what ships)
	rm -f "${STAGE_PLUGIN}/admin/css/ld-fg-admin-src.css"

	# Shell scripts and markdown anywhere (keep WordPress readme.txt)
	find "${STAGE_PLUGIN}" \( -name '*.sh' -o -name '*.md' \) -type f -print0 2>/dev/null \
		| while IFS= read -r -d '' path; do
			rm -f "$path"
		done

	# Any remaining hidden files/dirs (Plugin Check rejects these)
	find "${STAGE_PLUGIN}" \( -name '.*' -o -name '.*.*' \) ! -name '.' ! -name '..' -print0 2>/dev/null \
		| while IFS= read -r -d '' path; do
			rm -rf "$path"
		done
}

prune_stage

rm -f "$OUT"

ZIPPER_USED=""
if [[ -n "$ZIP_BIN" ]]; then
	ZIPPER_USED="zip"
	(
		cd "$STAGE"
		"$ZIP_BIN" -r "$OUT" "$ZIP_ROOT_NAME"
	)
elif [[ -n "$PS_BIN" ]]; then
	ZIPPER_USED="powershell"
	WIN_STAGE="$(to_win_path "$STAGE")"
	WIN_OUT="$(to_win_path "$OUT")"
	# CreateFromDirectory($STAGE) keeps the lancedesk-form-guard/ root folder in the archive.
	"$PS_BIN" -NoProfile -NonInteractive -Command \
		"Add-Type -AssemblyName System.IO.Compression.FileSystem; if (Test-Path -LiteralPath '$WIN_OUT') { Remove-Item -LiteralPath '$WIN_OUT' -Force }; [System.IO.Compression.ZipFile]::CreateFromDirectory('$WIN_STAGE', '$WIN_OUT')"
else
	echo "Error: no zipper available." >&2
	exit 1
fi

if [[ ! -s "$OUT" ]]; then
	echo "Error: zip was not created: ${OUT}" >&2
	exit 1
fi

# Build a file list for validation (unzip preferred; PowerShell fallback on Windows).
if [[ -n "$UNZIP_BIN" ]]; then
	ZIP_LIST="$("$UNZIP_BIN" -Z1 "$OUT")"
elif [[ -n "$PS_BIN" ]]; then
	WIN_OUT="$(to_win_path "$OUT")"
	ZIP_LIST="$("$PS_BIN" -NoProfile -NonInteractive -Command \
		"Add-Type -AssemblyName System.IO.Compression.FileSystem; \$z = [System.IO.Compression.ZipFile]::OpenRead('$WIN_OUT'); \$z.Entries | ForEach-Object { \$_.FullName.Replace('\\','/') }; \$z.Dispose()")"
else
	echo "Error: unzip is required to validate the package (or PowerShell on Windows)." >&2
	exit 1
fi

# Normalize separators (PowerShell may still emit backslashes depending on host).
ZIP_LIST="${ZIP_LIST//\\//}"
zip_contains() {
	grep -Fxq "$1" <<< "$ZIP_LIST"
}

for forbidden in \
	"${ZIP_ROOT_NAME}/bin/" \
	"${ZIP_ROOT_NAME}/tests/" \
	"${ZIP_ROOT_NAME}/docs/" \
	"${ZIP_ROOT_NAME}/html_templates/" \
	"${ZIP_ROOT_NAME}/resources/" \
	"${ZIP_ROOT_NAME}/dist/" \
	"${ZIP_ROOT_NAME}/.git/" \
	"${ZIP_ROOT_NAME}/.github/" \
	"${ZIP_ROOT_NAME}/.cursor/" \
	"${ZIP_ROOT_NAME}/node_modules/" \
	"${ZIP_ROOT_NAME}/vendor/" \
	"${ZIP_ROOT_NAME}/.gitignore" \
	"${ZIP_ROOT_NAME}/.distignore" \
	"${ZIP_ROOT_NAME}/.editorconfig" \
	"${ZIP_ROOT_NAME}/composer.json" \
	"${ZIP_ROOT_NAME}/composer.lock" \
	"${ZIP_ROOT_NAME}/README.md" \
	"${ZIP_ROOT_NAME}/zip-plugin.sh" \
	"${ZIP_ROOT_NAME}/repeat.sh"; do
	if grep -Fq "${forbidden}" <<< "$ZIP_LIST"; then
		echo "Error: forbidden path ${forbidden} found in ${OUT}" >&2
		exit 1
	fi
done

for required in \
	"${ZIP_ROOT_NAME}/lancedesk-form-guard.php" \
	"${ZIP_ROOT_NAME}/readme.txt" \
	"${ZIP_ROOT_NAME}/uninstall.php" \
	"${ZIP_ROOT_NAME}/includes/class-ld-fg-plugin.php" \
	"${ZIP_ROOT_NAME}/admin/class-ld-fg-admin.php" \
	"${ZIP_ROOT_NAME}/admin/css/ld-fg-admin.css" \
	"${ZIP_ROOT_NAME}/languages/index.php"; do
	if ! zip_contains "$required"; then
		echo "Error: required path missing from zip: ${required}" >&2
		exit 1
	fi
done

# Hidden files, shell scripts, markdown (readme.txt is allowed)
if grep -E '(^|/)\.[^/]+$' <<< "$ZIP_LIST" >/dev/null; then
	echo "Error: hidden files found in ${OUT}:" >&2
	grep -E '(^|/)\.[^/]+$' <<< "$ZIP_LIST" >&2 || true
	exit 1
fi
if grep -Ei '\.sh$' <<< "$ZIP_LIST" >/dev/null; then
	echo "Error: shell scripts found in ${OUT}:" >&2
	grep -Ei '\.sh$' <<< "$ZIP_LIST" >&2 || true
	exit 1
fi
if grep -Ei '\.md$' <<< "$ZIP_LIST" >/dev/null; then
	echo "Error: markdown files found in ${OUT}:" >&2
	grep -Ei '\.md$' <<< "$ZIP_LIST" >&2 || true
	exit 1
fi

echo "Built ${OUT}"
echo "Source: ${PLUGIN_ROOT}"
echo "Zipper: ${ZIPPER_USED}"
echo "Install: WP Admin → Plugins → Add New → Upload Plugin"
echo "         Upload ${OUT}"
echo "If update fails with 'Could not remove the current plugin', delete"
echo "wp-content/plugins/lancedesk-form-guard/ manually, then upload again."
