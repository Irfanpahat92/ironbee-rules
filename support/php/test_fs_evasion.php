#!/usr/bin/env php
<?php

$FILENAME = "test_fs_evasion.php";
$RANGE_MIN = 0;
$RANGE_MAX = 65536;
//$DEBUG = true;

function test($f) {
	global $FILENAME;

	$contents = @file_get_contents($f);	

	if (empty($contents)) {
		return false;
	} else {
		if (strpos($contents, $FILENAME) === false) {
			die("Did not actually get file content!");
		}

		return true;
	}
}

// The following two functions retrieved from Stack Overflow
// http://stackoverflow.com/questions/1805802/php-convert-unicode-codepoint-to-utf-8

function utf8($num)
{
    if($num<=0x7F)       return chr($num);
    if($num<=0x7FF)      return chr(($num>>6)+192).chr(($num&63)+128);
    if($num<=0xFFFF)     return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
    if($num<=0x1FFFFF)   return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128).chr(($num&63)+128);
    return '';
}

function uniord($c)
{
    $ord0 = ord($c{0}); if ($ord0>=0   && $ord0<=127) return $ord0;
    $ord1 = ord($c{1}); if ($ord0>=192 && $ord0<=223) return ($ord0-192)*64 + ($ord1-128);
    $ord2 = ord($c{2}); if ($ord0>=224 && $ord0<=239) return ($ord0-224)*4096 + ($ord1-128)*64 + ($ord2-128);
    $ord3 = ord($c{3}); if ($ord0>=240 && $ord0<=247) return ($ord0-240)*262144 + ($ord1-128)*4096 + ($ord2-128)*64 + ($ord3-128);
    return false;
}

function print_char($c) {
	print("    0x" . dechex($c) . "\n");
}

function print_platform_info() {
	print("Current PHP version: ");
	if (defined('PHP_VERSION_ID')) {
		print(PHP_VERSION);
	} else {
		print(phpversion());
	}

	print("\n\n");

	print("Operating system: " . php_uname() . "\n\n");

	if (defined('PHP_MAXPATHLEN')) {
		print("PHP_MAXPATHLEN: " . PHP_MAXPATHLEN . "\n\n");
	}

	print("Extensions: ");
	
	foreach (get_loaded_extensions() as $i => $ext) {
    	print($ext);
    	$version = phpversion($ext);
    	if (!empty($version)) {
    		print(" ($version) ");
    	}
	}

	print("\n");

	print("\n");
}

function pad_filename($FILENAME, $len) {
	$f = "./";
	
	while($len--) {
		$f = $f . "x";
	}

	$f = $f . "/../" . $FILENAME;

	return $f;
}


// -- Main ---

print_platform_info();


// First check that we can actually open the test file.
if (!test($FILENAME)) {
	die("Could not open test file: $FILENAME\n");
}

// --------------------

print("Max path length:\n");

$f = $FILENAME;
$len = 1;

for (;;) {
	$f = pad_filename($FILENAME, $len);

	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (!test($f)) {
		print("    " . strlen($f) . "\n");
		break;
	}

	$len++;
}

print("\n");

// Determine if the characters after maximum length are ignored.

$f = pad_filename($FILENAME, $len - 1);
if (!test($f)) {
	die("Unexpected failure.\n");
}

$f = $f . "x";

if (isset($DEBUG)) {
	print("Try: $f\n");
}

if (test($f)) {
	print("Adding content past MAX_PATH works (len " . strlen($f) . ").\n");
} else {
	print("Adding content past MAX_PATH does not work (len " . strlen($f) . ").\n");
}

print("\n");

// Determine . path truncation

$f = pad_filename($FILENAME, $len - 1);
if (!test($f)) {
	die("Unexpected failure.\n");
}

$f = $f . ".";

if (isset($DEBUG)) {
	print("Try: $f\n");
}

if (test($f)) {
	print("Path . truncation works.\n");
} else {
	print("Path . truncation does not work.\n");
}

print("\n");

// Determine .\ path truncation

$f = pad_filename($FILENAME, $len - 2);
if (!test($f)) {
	die("Unexpected failure.\n");
}

$f = $f . ".\\";

if (isset($DEBUG)) {
	print("Try: $f\n");
}

if (test($f)) {
	print("Path .\\ truncation works.\n");
} else {
	print("Path .\\ truncation does not work.\n");
}

print("\n");

// --------------------

print("Ignored when appended to a filename:\n");

$count = 0;

for ($c = $RANGE_MIN; $c < $RANGE_MAX; $c++) {
	$f = $FILENAME . utf8($c);

	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (test($f)) {
		print_char($c);
		$count++;
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

// --------------------

print("Ignored when prepended to a filename:\n");

$count = 0;

for ($c = $RANGE_MIN; $c < $RANGE_MAX; $c++) {
	$f = utf8($c) . $FILENAME;

	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (test($f)) {
		print_char($c);
		$count++;
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

// --------------------

print("Ignored inside a filename:\n");

$count = 0;

for ($c = $RANGE_MIN; $c < $RANGE_MAX; $c++) {
	$f = substr($FILENAME, 0, 5) . utf8($c) . substr($FILENAME, 5);
	
	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (test($f)) {
		print_char($c);
		$count++;
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

// --------------------

print("Filename terminators:\n");

$count = 0;

for ($c = $RANGE_MIN; $c < $RANGE_MAX; $c++) {
	$f = $FILENAME . utf8($c) . ".some.random.stuff";
	
	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (test($f)) {
		print_char($c);
		$count++;
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

// --------------------

print("One character wildcards:\n");

$count = 0;

$MY_FILENAME = substr($FILENAME, 0, strlen($FILENAME) - 1);
$last_char = substr($FILENAME, strlen($FILENAME) - 1, strlen($FILENAME));

for ($c = $RANGE_MIN; $c < $RANGE_MAX; $c++) {
	$f = $MY_FILENAME . chr($c);
	
	if (isset($DEBUG)) {
		print("Try: $f\n");
	}

	if (test($f)) {
		if (strtolower($f) != strtolower($FILENAME)) {
			print("[$f]\n");
			print("[$FILENAME]\n");
			print_char($c);
			$count++;
		}
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

// --------------------

print("Two character wildcards:\n");

$count = 0;

$MY_FILENAME = substr($FILENAME, 0, strlen($FILENAME) - 1);
$last_char = substr($FILENAME, strlen($FILENAME) - 1, strlen($FILENAME));

for ($c1 = $RANGE_MIN; $c1 < 256; $c1++) {
	for ($c2 = $RANGE_MIN; $c2 < 256; $c2++) {
		$f = $MY_FILENAME . chr($c1) . chr($c2);
	
		if (isset($DEBUG)) {
			print("Try: $f\n");
		}

		if (test($f)) {
			if (strtolower($f) != strtolower($FILENAME)) {
				print_char($c);
				$count++;
			}
		}
	}
}

if ($count == 0) {
	print("    none\n");
}

print("\n");

?>