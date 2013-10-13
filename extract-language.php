#!/usr/bin/env php
<?php
// Script to convert a set of language files in E. J. Klein Velderman's
// translation system to a GNU Gettext PO file.
//
// Usage:
// First, create a PO for the language you want to extract. Then, run
//
// extract-language.php <po_file> <english_lang_file> <other_lang_file>

include("po-parser/poparser.php");

define("MAX_LINE", 1024);

// Get options
if (count($argv) == 4) {
	$file_po = $argv[1];
	$file_en = $argv[2];
	$file_lang = $argv[3];
} else {
	echo "usage: extract-language.php <po_file> <english_lang_file> <other_lang_file>\n";
	die(1);
}

// Parse PO
$poparser = new PoParser();
$entries_po = $poparser->read($file_po);

// Parse language files
function parse_lng($file) {
	$entries_lng = array();
	$fp = fopen($file, 'r');
	$i = 0;

	while ($line = fgets($fp, MAX_LINE)) {
		if (preg_match('/^<\?/', $line) ||
			preg_match('/^\?>/', $line) ||
			preg_match('/^\/\//', $line) ||
			preg_match('/^\/\*/', $line) ||
			preg_match('/^ *$/', $line) ||
			preg_match('/^ \*/', $line) ||
			preg_match('/^define/', $line) ||
			strlen($line) == 0)
			continue;

		if (preg_match(
			// Backslash+quote is handled implicitly by requirement of trailing semicolon
			'/^\$lng_(?P<lng_id>[[:alnum:]_]+) *= *([\'"])(?P<msg_str>.+?)\2 *;/',
			$line,
			$matches))
			$entries_lng[$matches['lng_id']] = $matches['msg_str'];
		else
			fprintf(STDERR, "Line $i did not match: $line");

		$i++;
	}
	fclose($fp);
	return $entries_lng;
}

$entries_en = parse_lng($file_en);
$entries_lang = parse_lng($file_lang);
$entries_lang_count = 0;

foreach ($entries_lang as $lng_id => $msg_str) {
	$msg_id = $entries_en[$lng_id];
	if (array_search($msg_id, array_keys($entries_po)) &&
		strlen(trim($msg_str)) > 0) {
		$poparser->update_entry($msg_id, $msg_str);
		$entries_lang_count++;
	}
}

$poparser->write($file_po);

$stat_percent = round($entries_lang_count / count($entries_po) * 100, 1);

echo <<<STATS

Statistics:
$entries_lang_count translations found ($stat_percent % of POT).

Some manual work may remain. Check the following:
- That the file is converted to UTF-8 and openable in POEdit.

STATS;

?>
