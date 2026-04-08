# webpatser/portable-ascii

Modernized drop-in replacement for [`voku/portable-ascii`](https://github.com/voku/portable-ascii) — requires PHP 8.1+.

## Why?

The original `voku/portable-ascii` package (471M+ installs) still requires PHP 7.0 and has unresolved [PHP 8.4 deprecation warnings](https://github.com/voku/portable-ascii/issues/106). This fork modernizes the codebase while keeping full API compatibility.

**Zero breaking changes.** Same namespace, same methods, same behavior.

## What changed

- PHP 8.1 minimum (was 7.0)
- Typed properties throughout
- Fixed implicit nullable parameter deprecation (`to_transliterate()`)
- `str_contains()` / `str_starts_with()` replacing `strpos()` patterns
- Return types on all methods
- Test suite ported to Pest 4 (243 tests, 1600 assertions)

## Installation

```bash
composer require webpatser/portable-ascii
```

This automatically replaces `voku/portable-ascii` via Composer's `replace` directive. No code changes needed — `\voku\helper\ASCII` continues to work.

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

Works with Laravel 11, 12, and 13. No new extension requirements — `ext-intl` remains optional (suggested for faster transliteration).

## Fledge variant

For PHP 8.5 projects using [Fledge](https://github.com/webpatser/fledge-framework), there's an optimized variant that uses native `ext-intl` as the primary transliteration path:

```bash
composer require webpatser/fledge-portable-ascii
```

## Credits

This package is a modernized fork of [voku/portable-ascii](https://github.com/voku/portable-ascii) by [Lars Moelleken](https://github.com/voku). The original work, including all 193 Unicode-to-ASCII data tables and the transliteration engine, is his. Licensed under MIT.

## License

MIT
