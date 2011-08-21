<?php
// Scripts to migrate E. J. Klein Velderman's Lex translation system to GNU gettext.
// Phase II: Import Dutch translations

// WORK IN PROGRESS: part 3 below is not complete!

// 1: Scan English strings
//		Include the English language file, extract all globals and place them in a hash.
// 2: Scan Dutch strings
//      Include the Dutch language file, and put all translations in the same hash.
// 3: Output them to a prepared .po file.
// Fix up constants manually.

$pofilename = "includes/lang/nl_NL/LC_MESSAGES/nl.po";

// Scan all English strings
include("includes/lang/inc.US_lang.php");
foreach ($GLOBALS as $key => $string)
{
	if (strpos($key, "lng") === 0)
	{
		$strings[$key]['eng'] = $string;

		// Unset to make sure no English strings are mistaken for Dutch strings
		unset($$key);
	}
}

// Scan all Dutch strings
error_reporting(E_ALL ^ E_NOTICE); // Squelch warning about redefined constants
include("includes/lang/inc.NL_lang.php");
error_reporting(E_ALL);

foreach ($GLOBALS as $key => $string)
{
	if (strpos($key, "lng") === 0)
	{
		$strings[$key]['nl'] = $string;
	}
}

echo "Strings with missing translations:\n";
$missing = 0;
foreach ($strings as $key => $string)
{
	if (count($string) < 2)
	{
		echo "\n\$$key:\n";
		print_r($string);
		$missing++;
	}
}

if ($missing > 0)
{
	echo "\nPlease add missing strings and rerun the script.\n";
	exit(1);
}
else
{
	echo "None; all is well.\n";
}

// Import the prepared .po file

$pofile = file_get_contents($pofilename);

$progress['count'] = count($strings);
$progress['current'] = 0;
$progress['width'] = 80;

foreach ($strings as $key => $string)
{
	$progress['current']++;
	// Find the English string
	$eng_pos = strpos($pofile, "msgid \"". $string['eng'] ."\"");
	if ($eng_pos === false)
	{
		echo "\nDid not find $". $key .": \"". $string['eng'] ."\"\n";
	}
	else
		echo "=";
}

echo "\n";

?>
