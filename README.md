# webpatser/portable-ascii

Modernized drop-in replacement for [`voku/portable-ascii`](https://github.com/voku/portable-ascii) — requires PHP 8.1+.

## Why?

The original `voku/portable-ascii` package (471M+ installs) still requires PHP 7.0 and has unresolved [PHP 8.4 deprecation warnings](https://github.com/voku/portable-ascii/issues/106). This fork modernizes the codebase, fixes transliteration bugs, and is **36-50% faster** while keeping full API compatibility.

**Zero breaking changes.** Same namespace, same methods, same behavior.

## What changed

- PHP 8.1 minimum (was 7.0)
- Typed properties and return types throughout
- Fixed implicit nullable parameter deprecation (`to_transliterate()`)
- Replaced O(n*m) character replacement loop with `strtr()` — single C-level pass
- Adaptive sparse/dense detection for optimal replacement strategy
- `mb_str_split()` replacing `preg_match_all` for character splitting (when ext-mbstring available)
- Cascading transliteration: ext-intl -> ext-iconv -> manual byte decoding
- Inlined `is_ascii()` regex for faster hot-path checks
- Fixed Bulgarian, Ukrainian, Austrian (Eszett), and Hindi transliteration mappings
- Test suite ported to Pest 4 (247 tests, 1602 assertions)

## Performance

Benchmarked on PHP 8.5 with real-world inputs (German/French/Russian blog titles, product names, URL slugs, long text). Each profile was run in an isolated process with 20s CPU cooldown between runs, 5000 iterations, median of 5 runs.

| Profile | to_ascii | to_slugify | to_transliterate | Total | vs Original |
|---------|----------|------------|-----------------|-------|-------------|
| **original** (voku 2.0.3) | 742 ms | 936 ms | 1554 ms | 3953 ms | — |
| **fork** (no extensions) | 268 ms | 382 ms | 1540 ms | 2455 ms | **-38%** |
| **fork** + mbstring | 265 ms | 378 ms | 1174 ms | 2073 ms | **-48%** |
| **fork** + all extensions | 270 ms | 371 ms | 1171 ms | 2072 ms | **-48%** |

The `to_ascii()` and `to_slugify()` improvements are pure PHP — no extensions needed. `ext-mbstring` accelerates `to_transliterate()` by 24% via `mb_str_split()` replacing `preg_match_all` for character splitting.

Run the benchmark yourself:

```bash
php benchmark/run.php 5000           # fork vs original (side by side)
php benchmark/run-profiles.php 5000  # extension profile comparison
```

## Installation

```bash
composer require webpatser/portable-ascii
```

This automatically replaces `voku/portable-ascii` via Composer's `replace` directive. No code changes needed — `\voku\helper\ASCII` continues to work.

For best transliteration performance, install ext-mbstring:

```bash
sudo apt install php-mbstring
```

## Usage

```php
use voku\helper\ASCII;

// Transliterate to ASCII
ASCII::to_ascii('Düsseldorf', 'de');  // 'Duesseldorf'

// Check if string is ASCII
ASCII::is_ascii('hello');  // true

// Transliterate with fallback character
ASCII::to_transliterate('こんにちは', '?');  // 'konnichiha'

// Generate URL slug
ASCII::to_slugify('Hello Wörld!');  // 'hello-woerld'
```

## Compatibility

Works with Laravel 11, 12, and 13. All extensions are optional — the library auto-detects what's available and uses the fastest path.

| Extension | Impact | Used by |
|-----------|--------|---------|
| ext-mbstring | **-24%** on `to_transliterate()` | `mb_str_split()` for character splitting |
| ext-intl | Fastest transliteration via ICU | `to_transliterate()` in strict mode only |

## Credits

This package is a modernized fork of [voku/portable-ascii](https://github.com/voku/portable-ascii) by [Lars Moelleken](https://github.com/voku). The original work, including all 193 Unicode-to-ASCII data tables and the transliteration engine, is his. Licensed under MIT.

## License

MIT
