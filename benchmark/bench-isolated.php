<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
use voku\helper\ASCII;

$profile = $argv[1] ?? 'auto';
$iterations = 5000;

// Set profile
match ($profile) {
    'vanilla'  => ASCII::setCapabilities(['mbstring' => false, 'iconv' => false, 'intl' => false]),
    'mbstring' => ASCII::setCapabilities(['mbstring' => true,  'iconv' => false, 'intl' => false]),
    'iconv'    => ASCII::setCapabilities(['mbstring' => false, 'iconv' => true,  'intl' => false]),
    'all_ext'  => ASCII::setCapabilities(['mbstring' => true,  'iconv' => true,  'intl' => true]),
    default    => null, // auto-detect
};

$samples = [
    'blog_de'    => 'Wie man größere Probleme löst — ein Überblick für Anfänger',
    'blog_fr'    => "Les résultats définitifs de l'enquête française",
    'blog_ru'    => 'Как настроить сервер для продакшена',
    'product_de' => 'Bürostuhl ergonomisch höhenverstellbar — Größe XL',
    'slug_input' => '  --Héllo___Wörld---is    a Tëst!-- ',
    'seo_ro'     => 'Top 10 Restaurants în București — Ghid Complet 2024',
    'mixed'      => 'Ünïcödé → Recipe: Crème Brûlée & Naïve Café',
    'long_text'  => str_repeat('Ünïcödé Héllo Wörld! Straße Größe Müller. ', 20),
];

$methods = [
    'to_ascii'         => fn($s) => ASCII::to_ascii($s),
    'to_ascii(de)'     => fn($s) => ASCII::to_ascii($s, 'de'),
    'to_slugify'       => fn($s) => ASCII::to_slugify($s),
    'to_transliterate' => fn($s) => ASCII::to_transliterate($s),
];

$total = 0;
$results = [];
foreach ($methods as $method => $fn) {
    $subtotal = 0;
    foreach ($samples as $name => $str) {
        for ($i = 0; $i < 100; $i++) { $fn($str); }
        $times = [];
        for ($run = 0; $run < 5; $run++) {
            $start = hrtime(true);
            for ($i = 0; $i < $iterations; $i++) { $fn($str); }
            $times[] = (hrtime(true) - $start) / 1_000_000;
        }
        sort($times);
        $ms = $times[2]; // median
        $subtotal += $ms;
        $results[$method][$name] = $ms;
    }
    $results[$method]['_subtotal'] = $subtotal;
    $total += $subtotal;
}
$results['_total'] = $total;

echo json_encode($results);
