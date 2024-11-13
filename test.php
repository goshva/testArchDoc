<?php
//task 1
function readInput($filePath) {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $n = (int)$lines[0];
    $documents = array_slice($lines, 1, $n);
    $m = (int)$lines[$n + 1];
    $queries = array_slice($lines, $n + 2, $m);

    return [$documents, $queries];
}

function calculateRelevance($documents, $query) {
    $queryWords = explode(' ', $query);
    $relevance = [];

    foreach ($documents as $index => $doc) {
        $docWords = explode(' ', $doc);
        $score = 0;

        foreach ($queryWords as $word) {
            $score += substr_count(implode(' ', $docWords), $word);
        }

        if ($score > 0) {
            $relevance[] = ['score' => $score, 'index' => $index + 1];
        }
    }

    usort($relevance, function ($a, $b) {
        if ($b['score'] === $a['score']) {
            return $a['index'] <=> $b['index'];
        }
        return $b['score'] <=> $a['score'];
    });

    return array_slice(array_column($relevance, 'index'), 0, 5);
}

function processQueries($documents, $queries) {
    $results = [];

    foreach ($queries as $query) {
        $results[] = calculateRelevance($documents, $query);
    }

    return $results;
}

function writeOutput($results, $filePath) {
    $output = '';

    foreach ($results as $result) {
        $output .= implode(' ', $result) . "\n";
    }

    file_put_contents($filePath, $output);
}

// Main execution
$inputFile = "input.txt";
$outputFile = "output.txt";

list($documents, $queries) = readInput($inputFile);
$results = processQueries($documents, $queries);
writeOutput($results, $outputFile);
?>
<?php
// tests task1

use PHPUnit\Framework\TestCase;

class SearchIndexTest extends TestCase {

    public function testReadInput() {
        $input = "3\ni love coffee\ncoffee with milk and sugar\nfree tea for everyone\n2\ni love tea\nfree coffee\n";
        file_put_contents('test_input.txt', $input);

        list($documents, $queries) = readInput('test_input.txt');

        $this->assertEquals(["i love coffee", "coffee with milk and sugar", "free tea for everyone"], $documents);
        $this->assertEquals(["i love tea", "free coffee"], $queries);

        unlink('test_input.txt');
    }

    public function testCalculateRelevance() {
        $documents = ["i love coffee", "coffee with milk and sugar", "free tea for everyone"];
        $query = "coffee milk";

        $result = calculateRelevance($documents, $query);

        $this->assertEquals([2, 1], $result);
    }

    public function testProcessQueries() {
        $documents = ["i love coffee", "coffee with milk and sugar", "free tea for everyone"];
        $queries = ["coffee", "tea"];

        $results = processQueries($documents, $queries);

        $this->assertEquals([[1, 2], [3]], $results);
    }

    public function testWriteOutput() {
        $results = [[1, 2], [3]];
        $expectedOutput = "1 2\n3\n";

        writeOutput($results, 'test_output.txt');

        $this->assertEquals($expectedOutput, file_get_contents('test_output.txt'));

        unlink('test_output.txt');
    }

    public function testIntegration() {
        $input = "3\ni love coffee\ncoffee with milk and sugar\nfree tea for everyone\n2\ni love coffee\nfree tea\n";
        $expectedOutput = "1 2\n3\n";

        file_put_contents('test_input.txt', $input);
        
        list($documents, $queries) = readInput('test_input.txt');
        $results = processQueries($documents, $queries);
        writeOutput($results, 'test_output.txt');

        $this->assertEquals($expectedOutput, file_get_contents('test_output.txt'));

        unlink('test_input.txt');
        unlink('test_output.txt');
    }
}
?>
<?php

/*
 * Задача 2
 *
 * Имеетюся сайты-каталоги партномеров
 * https://www.promelec.ru/
 * https://www.chipdip.ru/
 * https://www.chip1stop.com/
 * Нужно узнать, на каком из них можно заказать макимальное количество диодов BAS521,115
 * Чтобы визуально понять, сколько диодов можно заказать на каждом из сайтов,
 * нужно ввести в строку поиска "BAS521,115" и посмотреть результаты выдачи
 *
 * Формат вывода
 * Выведите список сайтов со ссылкой на результаты поиска и количеством диодов которые можно заказать,
 * отсортированный по убыванию количеством диодов, доступных к заказу.
 * Если у двух сайтов одинаковое количество диодов, то первым будет тот,
 * у которого доменное имя идёт раньше в алфавитном (лексикографическом) порядке.
 * Пример:
 * https://www.chip1stop.com/result-url 120 000
 * https://www.chipdip.ru/result-url 30 000
 * https://www.promelec.ru/result-url 10 010
 */
//task 2

function getSearchResults($url, $query) {
    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . urlencode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Ошибка cURL: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    return $response;
}

function extractQuantity($html, $site) {
    // Парсинг HTML для извлечения количества диодов
    // В реальной задаче это нужно делать под конкретный сайт
    // Здесь примерный поиск через регулярные выражения

    switch ($site) {
        case 'promelec.ru':
            preg_match('/\d+[\s\,\d]* шт/', $html, $matches);
            break;
        case 'chipdip.ru':
            preg_match('/\d+[\s\,\d]* шт/', $html, $matches);
            break;
        case 'chip1stop.com':
            preg_match('/\d+[\s\,\d]* pcs/', $html, $matches);
            break;
        default:
            $matches = [];
    }

    if (!empty($matches)) {
        // Удаляем пробелы и запятые для получения чистого числа
        return (int) str_replace([',', ' ', 'шт', 'pcs'], '', $matches[0]);
    }

    return 0;
}

$sites = [
    'promelec.ru' => 'https://www.promelec.ru/search/?q=',
    'chipdip.ru' => 'https://www.chipdip.ru/search?searchtext=',
    'chip1stop.com' => 'https://www.chip1stop.com/search.do?key=',
];

$query = 'BAS521,115';
$results = [];

foreach ($sites as $site => $baseUrl) {
    echo "Обработка сайта: $site\n";
    $html = getSearchResults($baseUrl, $query);
    if ($html) {
        $quantity = extractQuantity($html, $site);
        $results[] = [
            'site' => $site,
            'url' => $baseUrl . urlencode($query),
            'quantity' => $quantity,
        ];
    } else {
        echo "Не удалось получить результаты для сайта $site\n";
    }
}

// Сортировка по количеству, а затем по имени сайта
usort($results, function ($a, $b) {
    if ($b['quantity'] === $a['quantity']) {
        return strcmp($a['site'], $b['site']);
    }
    return $b['quantity'] - $a['quantity'];
});

// Вывод результатов
foreach ($results as $result) {
    echo $result['url'] . ' ' . number_format($result['quantity'], 0, '.', ' ') . "\n";
}

// task2 tests
function runTests() {
    $testHtmlPromelec = '<html>...100 000 шт...</html>';
    $testHtmlChipdip = '<html>...200,000 шт...</html>';
    $testHtmlChip1Stop = '<html>...300000 pcs...</html>';

    $quantityPromelec = extractQuantity($testHtmlPromelec, 'promelec.ru');
    $quantityChipdip = extractQuantity($testHtmlChipdip, 'chipdip.ru');
    $quantityChip1Stop = extractQuantity($testHtmlChip1Stop, 'chip1stop.com');

    assert($quantityPromelec === 100000, 'Failed on promelec.ru');
    assert($quantityChipdip === 200000, 'Failed on chipdip.ru');
    assert($quantityChip1Stop === 300000, 'Failed on chip1stop.com');

    echo "All tests passed!\n";
}

runTests();

?>
<?php
//tests task2

function getSearchResults($url, $query) {
    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . urlencode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Ошибка cURL: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    return $response;
}

function extractQuantity($html, $site) {
    // Парсинг HTML для извлечения количества диодов
    // В реальной задаче это нужно делать под конкретный сайт
    // Здесь примерный поиск через регулярные выражения

    switch ($site) {
        case 'promelec.ru':
            preg_match('/\d+[\s\,\d]* шт/', $html, $matches);
            break;
        case 'chipdip.ru':
            preg_match('/\d+[\s\,\d]* шт/', $html, $matches);
            break;
        case 'chip1stop.com':
            preg_match('/\d+[\s\,\d]* pcs/', $html, $matches);
            break;
        default:
            $matches = [];
    }

    if (!empty($matches)) {
        // Удаляем пробелы и запятые для получения чистого числа
        return (int) str_replace([',', ' ', 'шт', 'pcs'], '', $matches[0]);
    }

    return 0;
}

$sites = [
    'promelec.ru' => 'https://www.promelec.ru/search/?q=',
    'chipdip.ru' => 'https://www.chipdip.ru/search?searchtext=',
    'chip1stop.com' => 'https://www.chip1stop.com/search.do?key=',
];

$query = 'BAS521,115';
$results = [];

foreach ($sites as $site => $baseUrl) {
    echo "Обработка сайта: $site\n";
    $html = getSearchResults($baseUrl, $query);
    if ($html) {
        $quantity = extractQuantity($html, $site);
        $results[] = [
            'site' => $site,
            'url' => $baseUrl . urlencode($query),
            'quantity' => $quantity,
        ];
    } else {
        echo "Не удалось получить результаты для сайта $site\n";
    }
}

// Сортировка по количеству, а затем по имени сайта
usort($results, function ($a, $b) {
    if ($b['quantity'] === $a['quantity']) {
        return strcmp($a['site'], $b['site']);
    }
    return $b['quantity'] - $a['quantity'];
});

// Вывод результатов
foreach ($results as $result) {
    echo $result['url'] . ' ' . number_format($result['quantity'], 0, '.', ' ') . "\n";
}

// Unit tests
function runTests() {
    $testHtmlPromelec = '<html>...100 000 шт...</html>';
    $testHtmlChipdip = '<html>...200,000 шт...</html>';
    $testHtmlChip1Stop = '<html>...300000 pcs...</html>';

    $quantityPromelec = extractQuantity($testHtmlPromelec, 'promelec.ru');
    $quantityChipdip = extractQuantity($testHtmlChipdip, 'chipdip.ru');
    $quantityChip1Stop = extractQuantity($testHtmlChip1Stop, 'chip1stop.com');

    assert($quantityPromelec === 100000, 'Failed on promelec.ru');
    assert($quantityChipdip === 200000, 'Failed on chipdip.ru');
    assert($quantityChip1Stop === 300000, 'Failed on chip1stop.com');

    echo "All tests passed!\n";
}

runTests();
?>
