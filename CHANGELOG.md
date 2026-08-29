# Changelog

All notable changes to the 360-degree feedback activity are documented here.

This is the changelog for the **401_STABLE** branch, which supports Moodle 4.1 to 4.5. It documents the v4.1.x
series only. For anything released before v4.1.0, and for the series currently in development, see the
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

## [v4.1.2] - 2026-08-22

Protects feedback that has already been given, and warns respondents about questions they have missed.

### Added

- Respondents are warned if they try to finalise their feedback with rating questions left unanswered, and can
  either go back and review them or save their progress and come back later.

### Changed

- The questions in a questionnaire can no longer be changed once respondents have started giving feedback.
  Changing them part way through left answers stranded and submissions incomplete.

### Fixed

- Coding style problems reported by the plugin CI.

## [v4.1.1] - 2025-10-21

Adds finer control over who can manage shared questions.

The same functional changes as v5.0.0 on the 5.0 branch, without the Bootstrap 5 work.

### Added

- Separate permissions for editing and deleting questions created by other people, so a teacher can manage
  their own questions without being able to change everyone else's.

### Fixed

- Problems reported by the plugin CI.

## [v4.1.0] - 2025-05-07

First release of the v4.1.x series, with accessibility improvements and question bank fixes.

### Added

- Test coverage reporting through Codecov.

### Changed

- Accessibility improvements to the activity's tables and to the course-level index page.
- Removed leftover styling classes from Bootstrap 2.

### Fixed

- A missing file include that broke the activity on Moodle 4.5.
- A missing notification string.
- The "add a question" dialogue appearing twice.
- The wrong question type being pre-selected when editing a question.
