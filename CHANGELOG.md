# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-04-08

### Changed
- PHP 8.1 minimum requirement (was PHP 7.0)
- Typed static properties (`?array` instead of docblock `@var`)
- Return types on all methods (`get_language()`, `getData()`)
- `str_contains()` replacing `\strpos($x, $y) !== false` patterns
- `str_starts_with()` replacing `\strpos($x, $y) === 0` patterns
- Test suite ported from PHPUnit to Pest 4

### Fixed
- PHP 8.4 deprecation: `to_transliterate()` parameter `$unknown` now has explicit `?string` type instead of untyped with nullable usage ([voku/portable-ascii#106](https://github.com/voku/portable-ascii/issues/106))

### Unchanged
- Namespace remains `voku\helper\ASCII` for drop-in compatibility
- All 15 public methods retain identical signatures and behavior
- All 193 Unicode-to-ASCII data files unchanged
- All 50+ language constants unchanged
- `ext-intl` remains optional (suggested, not required)
