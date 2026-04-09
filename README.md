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

Benchmarked on PHP 8.5 with real-world inputs (German/French/Russian blog titles, product names, URL slugs, long text). Each profile was run in an isolated process, 5000 iterations, median of 5 runs.

| Profile | to_ascii | to_slugify | to_transliterate | Total | vs Original |
|---------|----------|------------|-----------------|-------|-------------|
| **original** (voku 2.0.3) | 754 ms | 1020 ms | 1672 ms | 4247 ms | — |
| **fork** (no extensions) | 287 ms | 437 ms | 1702 ms | 2727 ms | **-36%** |
| **fork** + mbstring | 267 ms | 380 ms | 1191 ms | 2106 ms | **-50%** |
| **fork** + iconv | 275 ms | 374 ms | 1517 ms | 2431 ms | **-43%** |
| **fork** + all extensions | 269 ms | 394 ms | 1178 ms | 2112 ms | **-50%** |

The core `to_ascii()` and `to_slugify()` improvements are pure PHP — no extensions needed. Extensions accelerate `to_transliterate()` by up to 31%.

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

For best performance, install the suggested extensions:

```bash
# Most impactful for transliteration
sudo apt install php-intl php-mbstring

# Also helps
sudo apt install php-iconv
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
| ext-intl | Fastest transliteration via ICU | `to_transliterate()` (strict mode) |
| ext-mbstring | Faster character splitting | `to_transliterate()` fallback |
| ext-iconv | Fast ASCII transliteration | `to_transliterate()` fallback |

## Credits

This package is a modernized fork of [voku/portable-ascii](https://github.com/voku/portable-ascii) by [Lars Moelleken](https://github.com/voku). The original work, including all 193 Unicode-to-ASCII data tables and the transliteration engine, is his. Licensed under MIT.

## License

MIT
