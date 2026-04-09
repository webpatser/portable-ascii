# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2026-04-09

### Performance
- Replaced O(n*m) character replacement loop with `strtr()` — single C-level pass with adaptive sparse/dense detection
- `to_ascii()`: **-55%** on real-world inputs (German, French, Russian blog titles, slugs)
- `to_slugify()`: **-50%** via the same `strtr()` optimization
- `to_transliterate()`: **-21%** with ext-intl + ext-mbstring, **-31%** on the manual fallback path
- `is_ascii()`: **-35%** by inlining the regex pattern

### Added
- Runtime capability detection for PHP extensions (ext-mbstring, ext-intl)
- `mb_str_split()` replacing `preg_match_all` for character splitting when ext-mbstring available
- `setCapabilities()` method for benchmarking/testing extension profiles
- Benchmark scripts: `benchmark/run.php` (fork vs original) and `benchmark/run-profiles.php` (extension comparison)

### Fixed
- Bulgarian transliteration: Ц/ц → Ts/ts ([voku/portable-ascii#122](https://github.com/voku/portable-ascii/pull/122))
- Ukrainian transliteration: complete table per official standard ([voku/portable-ascii#120](https://github.com/voku/portable-ascii/pull/120))
- Austrian Eszett: ß → ss (was sz) for de_AT and fr_AT ([voku/portable-ascii#114](https://github.com/voku/portable-ascii/pull/114))
- Hindi transliteration corrections ([voku/portable-ascii#108](https://github.com/voku/portable-ascii/pull/108))

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
