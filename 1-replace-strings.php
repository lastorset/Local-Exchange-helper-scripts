<?php
// Scripts to migrate E. J. Klein Velderman's Lex translation system to GNU gettext.
// Phase I: Take English strings and reinsert them with Gettext markers in the PHP files.

// The algorithm is pretty brute-force, but for something that is only done once that should be OK.

include("includes/lang/inc.US_lang.php");

$dirs = array(".", "includes", "classes");
$excepts = array();
$total = 0;
$file_count = 0;
$globals_count = 0;
$orphaned_count = 0;

// Address components are constants rather than $lng_ variables
$constants = get_defined_constants(true);
$constants = $constants['user'];

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

		// Remove strings from globals declarations
		$file = preg_replace('#(\sglobal .*?)((, ?)?\$lng_\w*)+#', "$1", $file, -1 , $replace_count);
		$globals_count += $replace_count;

		// Remove empty globals declarations
		$file = preg_replace('#\s*global ;(\s// added by ejkv)?#', "", $file);

		// Remove "Added by ejkv" comments that were obsoleted by the above
		$file = preg_replace('#\s*// ((added|removed) (\$lng_\w*( and |, ?)?)+)+ (- )?by ejkv#', "", $file);
		$file = preg_replace('#\s*// replaced \$lng_\w*.*?(- )?by ejkv#', "", $file);

		// Search and replace
		foreach ($GLOBALS as $key => $string)
		{
			if (strpos($key, "lng") === 0)
			{
				$file = preg_replace('#\$'. $key. '\b#', "_(\"$string\")", $file, -1 , $replace_count);
				$count += $replace_count;
			}
		}

		foreach ($constants as $key => $string)
		{
			$file = preg_replace('#'. $key. '\b#', "_(\"$string\")", $file, -1 , $replace_count);
			$count += $replace_count;
		}

		// Orphaned strings (no English version, only Dutch)
		$file = preg_replace('#\$lng_(\w*)#', '_("$1" /* orphaned string */)', $file, -1 , $replace_count);
		$orphaned_count += $replace_count;

		// Print status
		echo "$count strings marked.\n";
		$total += $count;
		
		// Write file
		file_put_contents("$dir/$filename", $file);
	}
}

echo <<<STATS

Statistics:
$total strings marked for translation ($orphaned_count orphaned strings)
$globals_count globals removed
in $file_count files.

Some manual work may remain. Check the following:
- Weird comments
- Orphaned strings (marked as such in the code)

With or without addressing the above, you may now run the following command to generate a POT file:
xgettext --no-wrap --language=PHP --output=includes/lang/lex.pot *.php includes/*.php classes/*.php

STATS;
?>
