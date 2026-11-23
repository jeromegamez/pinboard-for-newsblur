<?php

// Config

$pattern = '/(?P<prefix><dc:subject>)(?P<subject>[^<]+)/u';
$buildDir = __DIR__ . '/.build/';
$feeds = [
    ['https://feeds.pinboard.in/rss/popular/', 'popular.xml']
];
$maxAttempts = 3;
$retryDelaySeconds = 5;
$timeoutSeconds = 15;

// Functions

$fetch = function (string $source) use ($maxAttempts, $retryDelaySeconds, $timeoutSeconds): string {
    $context = stream_context_create([
        'http' => [
            'timeout' => $timeoutSeconds,
        ],
    ]);

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $contents = file_get_contents($source, false, $context);

        if ($contents !== false && trim($contents) !== '') {
            return $contents;
        }

        if ($attempt < $maxAttempts) {
            sleep($retryDelaySeconds * $attempt);
        }
    }

    throw new RuntimeException("Failed to fetch feed after {$maxAttempts} attempts: {$source}");
};

$rewrite = function (string $contents) use ($pattern): string {
    $rewritten = preg_replace_callback($pattern, function (array $matches) {
        $newSubject = implode(', ', explode(' ', $matches['subject']));

        return $matches['prefix'] . $newSubject;
    }, $contents);

    if ($rewritten === null) {
        throw new RuntimeException('Failed to rewrite feed');
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

    $save($rewrite($fetch($url)), $filename);
}
