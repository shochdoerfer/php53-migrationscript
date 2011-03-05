<?php
/**
 * Copyright (c) 2011 Stephan Hochdoerfer <S.Hochdoerfer@bitExpert.de>
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */


// configuration
$sourceDir = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
$classes   = array();


// input validation: was source dir passed to the script?
if(empty($sourceDir))
{
	echo "Source Directory missing!\n";
	exit;
}
else if(!is_dir($sourceDir))
{
	echo "Source Directory {$sourceDir} does not exist!\n";
	exit;
}


// step 1: build mapping array (old classname => new classname)
$iterator = new RecursiveDirectoryIterator($sourceDir);
foreach(new RecursiveIteratorIterator($iterator) as $file)
{
	if(!is_file($file))
	{
		continue;
	}
	else if(false !== strpos($file, '/.svn/'))
	{
		// ignore .svn directories and it`s contents
		continue;
	}
	else if(false === strpos($file, '.php'))
	{
		// consider only php files
		continue;
	}

	$strippedFile = str_replace($sourceDir, '', $file);
	$namespace    = dirname($strippedFile);
	$namespace    = '\\'.str_replace('/', '\\', $namespace);

	$oldClassname = substr($strippedFile, 0, -4);
	$oldClassname = str_replace('/', '_', $oldClassname);

	$newClassname = substr($strippedFile, 0, -4);
	$newClassname = '\\'.str_replace('/', '\\', $newClassname);

	// store mapping
	$classes[$oldClassname] = $newClassname;
}


// process files again and convert the file contents
foreach(new RecursiveIteratorIterator($iterator) as $file)
{
	if(!is_file($file))
	{
		continue;
	}
	else if(false !== strpos($file, '/.svn/'))
	{
		// ignore .svn directories and it`s contents
		continue;
	}
	else if(false == strpos($file, '.php'))
	{
		// consider only php files
		continue;
	}

	// construct new classname
	$strippedFile = str_replace($sourceDir, '', $file);
	$oldClassname = substr($strippedFile, 0, -4);
	$oldClassname = '\\'.str_replace('/', '\\', $oldClassname);
	$classname    = basename($strippedFile);
	$classname    = substr($classname, 0, -4);

	// read source file
	$content = file_get_contents($file);

	// replace classname
	$content = str_replace(
		'class '.$oldClassname.' ',
		'class '.$classname.' ',
		$content
	);

	// replace interface name
	$content = str_replace(
		'interface '.$oldClassname.' ',
		'interface '.$classname.' ',
		$content
	);

	// add namespace declaration
	$namespace = dirname($strippedFile);
	$namespace = '\\'.str_replace('/', '\\', $namespace);
	$content = str_replace(
		"<?php\n",
		"<?php\nnamespace ".$namespace.";\n",
		$content
	);

	// replace reference to other classes and interfaces
	foreach($classes as $oldClassname => $newClassname)
	{
		$content = str_replace($oldClassname, $newClassname, $content);
	}

	// write content back to file
	file_put_contents($file, $content);
}
?>