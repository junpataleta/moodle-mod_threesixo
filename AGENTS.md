# Agent Instructions for `mod_threesixo`

This is a Moodle activity module for 360-degree feedback. Keep changes local to this plugin unless a task explicitly requires touching Moodle core.

## Start Here

- Read [README.md](README.md) for the feature overview, supported Moodle/PHP versions, and the canonical test commands.
- Prefer the existing PHPUnit and Behat coverage in [tests/](tests) when changing behavior.
- Follow Moodle coding style and plugin conventions already used in the codebase.

## Working Rules

- Preserve the plugin's existing PHP namespace and file layout.
- Keep PHP, Mustache, and AMD changes consistent with nearby patterns instead of introducing new abstractions.
- Treat [classes/api.php](classes/api.php), [classes/external.php](classes/external.php), [lib.php](lib.php), and [classes/helper.php](classes/helper.php) as the main behavior surfaces.
- Update or add tests alongside behavior changes; the repo already has focused coverage in [tests/](tests) and Behat scenarios in [tests/behat/](tests/behat).
- For frontend work, edit source files in [amd/src/](amd/src) and regenerate the built assets in [amd/build/](amd/build) only if the task requires committed build output.

## Common Pitfalls

- Moodle version compatibility matters; check [version.php](version.php) and the compatibility notes in [README.md](README.md) before changing APIs.
- Question bank and participant logic are tightly coupled to capability checks and test fixtures, so prefer small, targeted edits.
- External API changes should stay aligned with the PHPUnit coverage in [tests/external_test.php](tests/external_test.php).

## Useful References

- [README.md](README.md) for setup, tests, and release notes.
- [phpunit.xml](phpunit.xml) for the plugin test suite definition.
- [tests/api_test.php](tests/api_test.php) and [tests/lib_test.php](tests/lib_test.php) for behavior examples.
- [tests/behat/](tests/behat) for end-to-end user flows.
