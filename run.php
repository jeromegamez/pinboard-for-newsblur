<?php

// Config

$pattern = '/(?P<prefix><dc:subject>)(?P<subject>[^<]+)/u';
$buildDir = __DIR__ . '/.build/';
$feeds = [
    ['https://feeds.pinboard.in/rss/popular/', 'popular.xml']
];

// Functions

$rewrite = function (string $source) use ($pattern): string {
    $contents = file_get_contents($source);

    if ($contents === false || trim($contents) === '') {
        throw new RuntimeException("Failed to fetch feed: {$source}");
    }

    $rewritten = preg_replace_callback($pattern, function (array $matches) {
        $newSubject = implode(', ', explode(' ', $matches['subject']));

        return $matches['prefix'] . $newSubject;
    }, $contents);

    if ($rewritten === null) {
        throw new RuntimeException("Failed to rewrite feed: {$source}");
    }

    return $rewritten;
};

$save = function (string $contents, string $name) use ($buildDir) {
    file_put_contents($buildDir . $name, $contents);
};

// Execution

if (!file_exists($buildDir)) {
    mkdir($buildDir);
}

foreach ($feeds as $feed) {
    [$url, $filename] = $feed;

    $save($rewrite($url), $filename);
}
