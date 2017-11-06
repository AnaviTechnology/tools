<?php
$configTemplate = "template-labels.rtf";
$configPrintDir = "print";
$configPrintFilePrefix = "labels";

// Print only names as CVS
$justNames = false;

function createFileForPrinting($user)
{
	global $configTemplate;
	global $configPrintDir;
	global $configPrintFilePrefix;
	global $fileCount;

	if (false === file_exists($configPrintDir))
	{
		mkdir($configPrintDir);
	}
	$filePrint = "{$configPrintDir}/{$configPrintFilePrefix}{$fileCount}.rtf";
	if (false === copy($configTemplate, $filePrint))
	{
		echo "Failed to copy file: $configTemplate";
		return;
	}
	
	$content = file_get_contents($filePrint);
	for($iter=0;$iter<count($user);$iter++)
	{
		$content = str_replace("placeholder-address{$iter}",$user[$iter]['address'],$content);
		$content = str_replace("placeholder-tel{$iter}",$user[$iter]['tel'],$content);
	}

	file_put_contents($filePrint,$content);
	// Update file counter
	$fileCount++;
}

if (false === isset($argv[1]))
{
	echo "Please provide a CSV file.\n";
	return;
}

$shortopts = "f:";
$shortopts .= "n::";
$options = getopt($shortopts);

if (true === isset($options['n']))
{
	$justNames = true;
}

if (false === isset($options['f']))
{
	echo "Please provide CSV file with the -f option.";
	return;
}

$file = $options['f'];

if (false === file_exists($file))
{
	echo "File not found: {$file}\n";
	return;
}

$handle = @fopen($file, "r");
if (false === $handle)
{
	echo "Unable to open file: {$file}\n";
	return;
}

$labelCount = 0;
$fileCount = 0;
$user = array(); 
while (($line = fgets($handle, 4096)) !== false)
{
	$csv = str_getcsv($line);

	if (true === $justNames)
	{
		echo $text = "\"{$csv[0]} {$csv[1]}\"\n";
		continue;
	}

	// Name
	$text = "{$csv[0]} {$csv[1]}".'{\pard\par}';
	// Company
	if (false === empty(trim($csv[2])))
	{
		$text .= $csv[2].'{\pard\par}';
	}
	// Address
	$text .= $csv[3].'{\pard\par}';
	// Check if there is 2nd line in the address
	if (false === empty(trim($csv[4])))
	{
		$text .= $csv[4].'{\pard\par}';
	}
	// City, state and postal code
	$text .= "{$csv[5]} {$csv[6]} {$csv[7]}".'{\pard\par}';
	//Country
	$csv[8] = str_replace("United Kingdom of Great Britain and Northern Ireland", "United Kingdom (UK)", $csv[8]);
	$csv[8] = str_replace("Czechia", "Czech Republic", $csv[8]);
	$text .= "$csv[8]";

	$user[$labelCount]['address'] = $text;
	$user[$labelCount]['tel'] = (empty($csv[9])) ? '' : "tel: {$csv[9]}";

	//Update counter
	$labelCount++;
	if ((8 == $labelCount) && (false === $justNames))
	{
		createFileForPrinting($user);
		// reset array with user's data
		$user = array();
		$labelCount = 0;
	}
}
fclose($handle);

// Are there any labels left?
if ((0 < $labelCount) && (false === $justNames))
{
	createFileForPrinting($user);
}
?>
