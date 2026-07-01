#!/bin/bash
# Weekly WordPress cleanup + MariaDB maintenance for krivoshein.site
set -euo pipefail

SITE="krivoshein.site"
WP_PATH="/var/www/${SITE}/htdocs"
LOG_DIR="/var/log/${SITE}"
LOG_FILE="${LOG_DIR}/mysql-check.log"
LOCK_FILE="${LOG_DIR}/mysql-weekly.lock"
WP_CLI="/usr/local/bin/wp"

WP_CLEAN_FLAGS=(--path="${WP_PATH}" --skip-themes)
WP_DB_FLAGS=(--path="${WP_PATH}" --skip-plugins --skip-themes)

if [ "$(id -u)" -eq 0 ]; then
	exec runuser -u www-data -- "$0" "$@"
fi

mkdir -p "$LOG_DIR"

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
	echo "$(date -Iseconds) Another instance is running, exiting." >>"$LOG_FILE"
	exit 0
fi

log() {
	echo "$(date -Iseconds) $*" | tee -a "$LOG_FILE"
}

wp_run() {
	log "RUN: wp $*"
	if ! "$WP_CLI" "$@" >>"$LOG_FILE" 2>&1; then
		return 1
	fi
	return 0
}

wp_cleanup_posts() {
	local status="$1"
	local ids

	ids=$("$WP_CLI" "${WP_CLEAN_FLAGS[@]}" post list --post_status="$status" --format=ids 2>>"$LOG_FILE" || true)
	if [ -z "$ids" ]; then
		log "No posts with status=${status} to delete"
		return 0
	fi

	log "Deleting posts with status=${status}: ${ids}"
	wp_run "${WP_CLEAN_FLAGS[@]}" post delete $ids --force
}

main() {
	log "=== Weekly maintenance started ==="

	log "Phase A: WordPress cleanup"

	log "Deleting expired transients"
	wp_run "${WP_CLEAN_FLAGS[@]}" transient delete --expired

	log "Flushing object cache"
	wp_run "${WP_CLEAN_FLAGS[@]}" cache flush

	log "Cleaning action scheduler (older than 31 days)"
	wp_run "${WP_CLEAN_FLAGS[@]}" action-scheduler clean --before='31 days ago'

	wp_cleanup_posts auto-draft
	wp_cleanup_posts trash

	log "Phase B: MariaDB maintenance"

	if wp_run "${WP_DB_FLAGS[@]}" db check --auto-repair; then
		log "db check passed, running optimize"
		wp_run "${WP_DB_FLAGS[@]}" db optimize
	else
		log "ERROR: db check failed, skipping optimize"
		exit 1
	fi

	log "=== Weekly maintenance completed successfully ==="
}

main "$@"
