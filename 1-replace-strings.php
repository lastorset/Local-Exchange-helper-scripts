<?php
// Scripts to migrate E. J. Klein Velderman's Lex translation system to GNU gettext.
// Phase I: Take English strings and reinsert them with Gettext markers in the PHP files.

// The algorithm is pretty brute-force, but for something that is only done once that should be OK.


// BUG: Need to replace only exact matches of $lng_ variable names.
// Otherwise, $lng_new will partially replace $lng_news_and_events.


include("includes/lang/inc.US_lang.php");

$dirs = array(".", "includes", "classes");
$excepts = array("inc.config.php"); // temporarily, since it is not version controlled.
$total = 0;
$file_count = 0;
$globals_count = 0;

foreach ($dirs as $dir)
{
	// Read file list
	$files = scandir($dir);

	foreach ($files as $filename)
	{
		if (strpos($filename, ".php"))
			// Read file
			$file = file_get_contents($dir .'/'. $filename);
		else
			continue;

		if (in_array($filename, $excepts))
		{
			echo "Skipped $filename\n";
			continue;
		}

		// Print status
		echo "$dir/$filename: ";
		$count = 0;
		$file_count++;

		// Remove strings from globals list
		$file = preg_replace('#(	global .*?)((, ?)?\$lng_\w*)+#', "$1", $file, -1 , $string_count);

		$globals_count += $string_count;

		// Search and replace
		foreach ($GLOBALS as $key => $string)
		{
			if (strpos($key, "lng") === 0)
			{
				$file = preg_replace('#\$'. $key. '\b#', "_(\"$string\")", $file, -1 , $string_count);
				$count += $string_count;
			}
		}

		// Print status
		echo "$count strings marked.\n";
		$total += $count;
		
		// Write file
		file_put_contents("$dir/$filename", $file);
	}
}

echo "$total strings marked for translation ($globals_count 'global' declarations) in $file_count files.\n";
?>
