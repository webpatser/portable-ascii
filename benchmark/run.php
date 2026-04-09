<?php

declare(strict_types=1);

/**
 * Benchmark: webpatser/portable-ascii vs voku/portable-ascii
 *
 * Compares performance of the modernized fork against the original library.
 * Both are loaded via separate composer installs to avoid conflicts.
 *
 * Usage: php benchmark/run.php [iterations]
 */

$iterations = (int) ($argv[1] ?? 5000);

// --- Test data ---

$samples = [
    'short_ascii'    => 'Hello World',
    'short_unicode'  => 'Ünïcödé Strïng',
    'german'         => 'Ä Ö Ü ß Straße Köln Düsseldorf Müller Größe Bäcker Würstchen',
    'russian'        => 'Привет мир! Это тестовая строка для бенчмарка.',
    'ukrainian'      => 'Україна — держава у Східній Європі, Чернівці, щастя.',
    'chinese'        => '你好世界 这是一个测试字符串',
    'japanese'       => '日本語テスト文字列 こんにちは世界',
    'mixed'          => 'Héllo Wörld! Ünïcödé → ASCII ♥ 日本語 Ελληνικά العربية',
    'long_text'      => str_repeat('Ünïcödé Héllo Wörld! Straße Größe Müller. ', 20),
    'slug_input'     => '  --Héllo___Wörld---is    a Tëst!-- ',
    'already_ascii'  => 'This is already plain ASCII text with no special characters at all 1234567890',
    'emoji_mixed'    => 'Hello 🌍 World 🎉 Ünïcödé ♥ Tëst',
];

$languages = ['en', 'de', 'ru', 'uk', 'fr', 'el', 'zh', 'ja', 'bg'];

// --- Benchmark helpers ---

function benchmark(callable $fn, int $iterations): array
{
    // Warmup
    for ($i = 0; $i < min(100, $iterations); $i++) {
        $fn();
    }

    $times = [];
    for ($i = 0; $i < 5; $i++) {
        $start = hrtime(true);
        for ($j = 0; $j < $iterations; $j++) {
            $fn();
        }
        $times[] = (hrtime(true) - $start) / 1_000_000; // ms
    }

    sort($times);
    return [
        'median_ms' => $times[2],
        'min_ms'    => $times[0],
        'max_ms'    => $times[4],
    ];
}

function formatResult(array $original, array $fork): string
{
    $diff = (($fork['median_ms'] - $original['median_ms']) / $original['median_ms']) * 100;
    $sign = $diff > 0 ? '+' : '';
    $indicator = $diff < -1 ? '✅' : ($diff > 1 ? '❌' : '➖');

    return sprintf(
        "  orig: %7.2f ms | fork: %7.2f ms | %s%5.1f%% %s",
        $original['median_ms'],
        $fork['median_ms'],
        $sign,
        $diff,
        $indicator
    );
}

// --- Load both libraries ---

// Fork (current repo)
require __DIR__ . '/../vendor/autoload.php';
$forkClass = \voku\helper\ASCII::class;

// Original (installed in benchmark/vendor-original)
$originalAutoload = __DIR__ . '/vendor-original/autoload.php';
if (!file_exists($originalAutoload)) {
    echo "Installing original voku/portable-ascii for comparison...\n";
    $dir = __DIR__ . '/vendor-original';
    @mkdir($dir, 0755, true);

    // Create a minimal composer setup
    file_put_contents(__DIR__ . '/composer-original.json', json_encode([
        'require' => ['voku/portable-ascii' => '^2.0'],
        'config'  => ['vendor-dir' => 'vendor-original'],
    ], JSON_PRETTY_PRINT));

    $result = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && composer install --no-dev --prefer-dist --no-interaction --no-progress -q 2>&1');
    if (!file_exists($originalAutoload)) {
        echo "Failed to install original library:\n$result\n";
        exit(1);
    }
    echo "Done.\n\n";
}

// We can't load both in the same process (same class name).
// Instead, we'll run the original in a subprocess.

$subBenchmark = __DIR__ . '/run-original.php';
file_put_contents($subBenchmark, '<?php
declare(strict_types=1);
require __DIR__ . "/vendor-original/autoload.php";

$data = json_decode($argv[1], true);
$iterations = (int) $argv[2];

function benchmark(callable $fn, int $iterations): array {
    for ($i = 0; $i < min(100, $iterations); $i++) { $fn(); }
    $times = [];
    for ($i = 0; $i < 5; $i++) {
        $start = hrtime(true);
        for ($j = 0; $j < $iterations; $j++) { $fn(); }
        $times[] = (hrtime(true) - $start) / 1_000_000;
    }
    sort($times);
    return ["median_ms" => $times[2], "min_ms" => $times[0], "max_ms" => $times[4]];
}

$results = [];

// to_ascii benchmarks
foreach ($data["samples"] as $name => $str) {
    $results["to_ascii"][$name] = benchmark(fn() => \voku\helper\ASCII::to_ascii($str), $iterations);
}

// to_ascii with language
foreach ($data["languages"] as $lang) {
    $str = $data["samples"]["mixed"];
    $results["to_ascii_lang"][$lang] = benchmark(fn() => \voku\helper\ASCII::to_ascii($str, $lang), $iterations);
}

// to_slugify
foreach ($data["samples"] as $name => $str) {
    $results["to_slugify"][$name] = benchmark(fn() => \voku\helper\ASCII::to_slugify($str), $iterations);
}

// to_transliterate
foreach ($data["samples"] as $name => $str) {
    $results["to_transliterate"][$name] = benchmark(fn() => \voku\helper\ASCII::to_transliterate($str), $iterations);
}

// is_ascii
foreach ($data["samples"] as $name => $str) {
    $results["is_ascii"][$name] = benchmark(fn() => \voku\helper\ASCII::is_ascii($str), $iterations);
}

// charsArrayWithOneLanguage (cold + warm)
foreach ($data["languages"] as $lang) {
    $results["charsArray"][$lang] = benchmark(fn() => \voku\helper\ASCII::charsArrayWithOneLanguage($lang), $iterations);
}

echo json_encode($results);
');

// --- Run original benchmark ---

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  portable-ascii Benchmark: original vs fork                 ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Iterations per test: " . str_pad((string) $iterations, 36) . " ║\n";
echo "║  PHP: " . str_pad(PHP_VERSION, 52) . " ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$payload = json_encode(['samples' => $samples, 'languages' => $languages]);
echo "Running original library benchmark...\n";
$originalJson = shell_exec('php ' . escapeshellarg($subBenchmark) . ' ' . escapeshellarg($payload) . ' ' . $iterations);
$originalResults = json_decode($originalJson, true);

if (!$originalResults) {
    echo "ERROR: Failed to run original benchmark.\nOutput: $originalJson\n";
    exit(1);
}

echo "Running fork benchmark...\n\n";

// --- Run fork benchmarks ---

$forkResults = [];

// to_ascii
foreach ($samples as $name => $str) {
    $forkResults['to_ascii'][$name] = benchmark(fn() => \voku\helper\ASCII::to_ascii($str), $iterations);
}

// to_ascii with language
foreach ($languages as $lang) {
    $str = $samples['mixed'];
    $forkResults['to_ascii_lang'][$lang] = benchmark(fn() => \voku\helper\ASCII::to_ascii($str, $lang), $iterations);
}

// to_slugify
foreach ($samples as $name => $str) {
    $forkResults['to_slugify'][$name] = benchmark(fn() => \voku\helper\ASCII::to_slugify($str), $iterations);
}

// to_transliterate
foreach ($samples as $name => $str) {
    $forkResults['to_transliterate'][$name] = benchmark(fn() => \voku\helper\ASCII::to_transliterate($str), $iterations);
}

// is_ascii
foreach ($samples as $name => $str) {
    $forkResults['is_ascii'][$name] = benchmark(fn() => \voku\helper\ASCII::is_ascii($str), $iterations);
}

// charsArrayWithOneLanguage
foreach ($languages as $lang) {
    $forkResults['charsArray'][$lang] = benchmark(fn() => \voku\helper\ASCII::charsArrayWithOneLanguage($lang), $iterations);
}

// --- Output results ---

$sections = [
    'to_ascii'         => 'ASCII::to_ascii()',
    'to_ascii_lang'    => 'ASCII::to_ascii() with language',
    'to_slugify'       => 'ASCII::to_slugify()',
    'to_transliterate' => 'ASCII::to_transliterate()',
    'is_ascii'         => 'ASCII::is_ascii()',
    'charsArray'       => 'ASCII::charsArrayWithOneLanguage()',
];

$totalOriginal = 0;
$totalFork = 0;

foreach ($sections as $key => $title) {
    echo "━━━ {$title} ━━━\n";

    foreach ($originalResults[$key] as $name => $origResult) {
        $forkResult = $forkResults[$key][$name];
        echo "  {$name}:\n";
        echo formatResult($origResult, $forkResult) . "\n";

        $totalOriginal += $origResult['median_ms'];
        $totalFork += $forkResult['median_ms'];
    }
    echo "\n";
}

// --- Summary ---

$totalDiff = (($totalFork - $totalOriginal) / $totalOriginal) * 100;
$sign = $totalDiff > 0 ? '+' : '';

echo "═══════════════════════════════════════════════════════════════\n";
echo sprintf("  TOTAL:  orig: %7.2f ms | fork: %7.2f ms | %s%.1f%%\n",
    $totalOriginal, $totalFork, $sign, $totalDiff);
echo "═══════════════════════════════════════════════════════════════\n";

// Save JSON results
$output = [
    'php_version' => PHP_VERSION,
    'iterations'  => $iterations,
    'date'        => date('Y-m-d H:i:s'),
    'original'    => $originalResults,
    'fork'        => $forkResults,
    'summary'     => [
        'total_original_ms' => $totalOriginal,
        'total_fork_ms'     => $totalFork,
        'diff_percent'      => round($totalDiff, 2),
    ],
];

file_put_contents(__DIR__ . '/results.json', json_encode($output, JSON_PRETTY_PRINT));
echo "\nDetailed results saved to benchmark/results.json\n";
