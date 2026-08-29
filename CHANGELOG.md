# Changelog

All notable changes to the 360-degree feedback activity are documented here.

This is the changelog for the **500_STABLE** branch, which supports Moodle 5.0. It documents the v5.0.x series
only. For anything released before v5.0.0, and for the series currently in development, see the
[changelog on main](https://github.com/junpataleta/moodle-mod_threesixo/blob/main/CHANGELOG.md), which carries
the plugin's full release history.

Each release starts with a one-line summary, which is also used as the title of its GitHub release. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

Fixes to how shared questions are handled when an activity is backed up and restored.

### Fixed

- Backups now contain only the questions the activity actually uses. Previously a backup included every
  question on the site, and restoring it added them all to the target site's question bank.
- If a question has been deleted from the question bank since the backup was made, the restore recreates it and
  gives it to the person doing the restore. Recreated questions previously had no owner, so nobody could edit
  or delete them without site-level permissions.
- When the question bank holds more than one copy of the same question, a restore now uses the copy that was
  added first, instead of whichever the database happened to return.

## [v5.0.2] - 2026-08-22

Protects feedback that has already been given, and warns respondents about questions they have missed.

### Added

- Respondents are warned if they try to finalise their feedback with rating questions left unanswered, and can
  either go back and review them or save their progress and come back later.

### Changed

- The questions in a questionnaire can no longer be changed once respondents have started giving feedback.
  Changing them part way through left answers stranded and submissions incomplete.

## [v5.0.1] - 2026-06-20

Replaces the last of the old JavaScript.

The same changes as v5.1.0 on main, without the parts that only apply to Moodle 5.1.

### Changed

- The activity no longer uses jQuery.
- The questionnaire view and its dialogues now use Moodle's current JavaScript and modal APIs.

### Fixed

- Coding style problems reported by the plugin CI.

## [v5.0.0] - 2025-10-21

First release of the v5.0.x series, for Moodle 5.0.

### Added

- Separate permissions for editing and deleting questions created by other people, so a teacher can manage
  their own questions without being able to change everyone else's.

### Changed

- Updated for Bootstrap 5, correcting styling and spacing throughout the activity.

### Fixed

- Problems reported by the plugin CI.
