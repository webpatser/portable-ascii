<?php

declare(strict_types=1);

/**
 * Benchmark: original vs fork with different extension profiles.
 *
 * Usage: php benchmark/run-profiles.php [iterations]
 */

$iterations = (int) ($argv[1] ?? 5000);

require __DIR__ . '/../vendor/autoload.php';

// --- Test data ---

$samples = [
    'blog_de'       => 'Wie man größere Probleme löst — ein Überblick für Anfänger',
    'blog_fr'       => "Les résultats définitifs de l'enquête française",
    'blog_ru'       => 'Как настроить сервер для продакшена',
    'product_de'    => 'Bürostuhl ergonomisch höhenverstellbar — Größe XL',
    'slug_input'    => '  --Héllo___Wörld---is    a Tëst!-- ',
    'url_path'      => '/blog/2024/déjà-vu-café-naïve',
    'seo_ro'        => 'Top 10 Restaurants în București — Ghid Complet 2024',
    'japanese'      => '東京タワーの観光ガイド',
    'mixed'         => 'Ünïcödé → Recipe: Crème Brûlée & Naïve Café',
    'long_text'     => str_repeat('Ünïcödé Héllo Wörld! Straße Größe Müller. ', 20),
];

$methods = [
    'to_ascii'         => fn(string $s) => \voku\helper\ASCII::to_ascii($s),
    'to_ascii(de)'     => fn(string $s) => \voku\helper\ASCII::to_ascii($s, 'de'),
    'to_slugify'       => fn(string $s) => \voku\helper\ASCII::to_slugify($s),
    'to_transliterate' => fn(string $s) => \voku\helper\ASCII::to_transliterate($s),
    'is_ascii'         => fn(string $s) => \voku\helper\ASCII::is_ascii($s),
];

$profiles = [
    'vanilla'       => ['mbstring' => false, 'intl' => false],
    'mbstring'      => ['mbstring' => true,  'intl' => false],
    'all_ext'       => ['mbstring' => true,  'intl' => true],
];

// --- Benchmark helpers ---

function bench(callable $fn, int $iterations): float
{
    // Warmup
    for ($i = 0; $i < min(50, $iterations); $i++) {
        $fn();
    }

    $times = [];
    for ($run = 0; $run < 3; $run++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $fn();
        }
        $times[] = (hrtime(true) - $start) / 1_000_000;
    }

    sort($times);
    return $times[1]; // median
}

// --- Run original in subprocess ---

$subScript = __DIR__ . '/run-original.php';
if (!file_exists(__DIR__ . '/vendor-original/autoload.php')) {
    echo "Original library not installed. Run benchmark/run.php first.\n";
    exit(1);
}

// Build original benchmark data
file_put_contents($subScript, '<?php
declare(strict_types=1);
require __DIR__ . "/vendor-original/autoload.php";

$data = json_decode($argv[1], true);
$iterations = (int) $argv[2];

function bench(callable $fn, int $iterations): float {
    for ($i = 0; $i < min(50, $iterations); $i++) { $fn(); }
    $times = [];
    for ($run = 0; $run < 3; $run++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) { $fn(); }
        $times[] = (hrtime(true) - $start) / 1_000_000;
    }
    sort($times);
    return $times[1];
}

$results = [];
foreach ($data["samples"] as $name => $str) {
    $results["to_ascii"][$name]         = bench(fn() => \voku\helper\ASCII::to_ascii($str), $iterations);
    $results["to_ascii(de)"][$name]     = bench(fn() => \voku\helper\ASCII::to_ascii($str, "de"), $iterations);
    $results["to_slugify"][$name]       = bench(fn() => \voku\helper\ASCII::to_slugify($str), $iterations);
    $results["to_transliterate"][$name] = bench(fn() => \voku\helper\ASCII::to_transliterate($str), $iterations);
    $results["is_ascii"][$name]         = bench(fn() => \voku\helper\ASCII::is_ascii($str), $iterations);
}
echo json_encode($results);
');

echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║  portable-ascii: Original vs Fork Extension Profiles                    ║\n";
echo "╠═══════════════════════════════════════════════════════════════════════════╣\n";
echo "║  PHP: " . str_pad(PHP_VERSION, 66) . "║\n";
echo "║  Iterations: " . str_pad((string) $iterations, 59) . "║\n";
echo "║  Available: " . str_pad(implode(', ', array_filter([
    extension_loaded('mbstring') ? 'mbstring' : '',
    extension_loaded('intl') ? 'intl' : '',
])), 60) . "║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

// --- Run original ---
echo "Running original (voku/portable-ascii 2.0.3)...\n";
$payload = json_encode(['samples' => $samples]);
$origJson = shell_exec('php ' . escapeshellarg($subScript) . ' ' . escapeshellarg($payload) . ' ' . $iterations);
$origResults = json_decode($origJson, true);
if (!$origResults) {
    echo "ERROR running original benchmark.\n";
    exit(1);
}

// --- Run fork profiles ---
$forkResults = [];
foreach ($profiles as $profileName => $caps) {
    echo "Running fork ($profileName)...\n";
    \voku\helper\ASCII::setCapabilities($caps);

    $forkResults[$profileName] = [];
    foreach ($methods as $method => $fn) {
        foreach ($samples as $sampleName => $str) {
            $forkResults[$profileName][$method][$sampleName] = bench(fn() => $fn($str), $iterations);
        }
    }
}

// Reset
\voku\helper\ASCII::setCapabilities(null);

// --- Output ---

echo "\n";

foreach ($methods as $method => $fn) {
    echo "━━━ {$method}() ━━━\n";
    echo str_pad('', 16);
    echo str_pad('original', 12);
    foreach ($profiles as $pName => $_) {
        echo str_pad($pName, 12);
    }
    echo "\n";

    $totals = ['original' => 0];
    foreach ($profiles as $pName => $_) {
        $totals[$pName] = 0;
    }

    foreach ($samples as $sampleName => $str) {
        echo str_pad($sampleName, 16);

        $origMs = $origResults[$method][$sampleName];
        $totals['original'] += $origMs;
        echo str_pad(sprintf('%.1f', $origMs), 12);

        foreach ($profiles as $pName => $_) {
            $forkMs = $forkResults[$pName][$method][$sampleName];
            $totals[$pName] += $forkMs;
            $pct = (($forkMs - $origMs) / $origMs) * 100;
            $sign = $pct > 0 ? '+' : '';
            $indicator = $pct < -5 ? '↓' : ($pct > 5 ? '↑' : '=');
            echo str_pad(sprintf('%.1f %s%d%%%s', $forkMs, $sign, (int)$pct, $indicator), 12);
        }
        echo "\n";
    }

    // Subtotals
    echo str_pad('SUBTOTAL', 16);
    echo str_pad(sprintf('%.1f', $totals['original']), 12);
    foreach ($profiles as $pName => $_) {
        $pct = (($totals[$pName] - $totals['original']) / $totals['original']) * 100;
        $sign = $pct > 0 ? '+' : '';
        echo str_pad(sprintf('%.1f %s%d%%', $totals[$pName], $sign, (int)$pct), 12);
    }
    echo "\n\n";
}

// --- Grand totals ---
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo str_pad('GRAND TOTAL', 16);
$grandOrig = 0;
$grandFork = [];
foreach ($profiles as $pName => $_) {
    $grandFork[$pName] = 0;
}

foreach ($methods as $method => $_) {
    foreach ($samples as $sampleName => $_2) {
        $grandOrig += $origResults[$method][$sampleName];
        foreach ($profiles as $pName => $_3) {
            $grandFork[$pName] += $forkResults[$pName][$method][$sampleName];
        }
    }
}

echo str_pad(sprintf('%.0f ms', $grandOrig), 12);
foreach ($profiles as $pName => $_) {
    $pct = (($grandFork[$pName] - $grandOrig) / $grandOrig) * 100;
    $sign = $pct > 0 ? '+' : '';
    echo str_pad(sprintf('%.0f ms %s%d%%', $grandFork[$pName], $sign, (int)$pct), 12);
}
echo "\n═══════════════════════════════════════════════════════════════════════════\n";
echo "\nLegend: ↓ = faster than original, ↑ = slower, = = within 5%\n";
