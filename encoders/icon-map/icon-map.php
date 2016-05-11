<?php
define('ICONS_PATH', './icons');

header('Access-Control-Allow-Origin: *');

$fingerprint = $_GET['fingerprint'];
$part = $_GET['part'];
$group = $_GET['group'];
$cols = $_GET['cols'];

if ($group == null) $group = 4;
if ($cols == null) $cols = 5;

$icons = array();
for ($i=0; $i<10000; $i++)
	array_push( $icons, (Object) [ 'filename' => sprintf('%04d.png', $i) ] );

$fingerprintGroups = getFingerprintGroups($fingerprint, $group);

$image = new Imagick();
$image->setBackgroundColor(new ImagickPixel('transparent')); 
$rowImage = null;
$colCount = 0;

if ($part == null) {
		foreach($fingerprintGroups as $fingerprintGroup) {
			if ($rowImage == null) {
				$rowImage = new Imagick;
				$colCount = 0;
			}
			$rowImage->addImage(renderIcon($fingerprintGroup, $icons));
			$colCount = $colCount + 1;
			if ($colCount == intval($cols)) {
				$rowImage->resetIterator();
				$combinedRow = $rowImage->appendImages(false);
				$combinedRow->setImageFormat('png');
				$image->addImage($combinedRow);
				$rowImage = null;
			}
		}
		if ($rowImage != null) {
			while ($colCount < $cols) {
				$rowImage->addImage(renderPadding());
				$colCount = $colCount + 1;
			}	
			$rowImage->resetIterator();
			$combinedRow = $rowImage->appendImages(false);
			$combinedRow->setImageFormat('png');
			$image->addImage($combinedRow);
		}
	}
else
	$image->addImage(renderIcon($fingerprintGroups[$part], $icons));

$image->resetIterator();
$combined = $image->appendImages(true);
$combined->setBackgroundColor(new ImagickPixel('transparent')); 
$combined->setImageFormat('png');

header('Content-type: image/png');
echo $combined;

// ----------------------------------------------------------------------------

function getFingerprintGroups($fingerprint, $group) {
	// pad the fingerprint to be a whole number of groups long
	$numberOfGroups = ceil(strlen($fingerprint)/$group);
	while (strlen($fingerprint) < $numberOfGroups * $group)
		$fingerprint = $fingerprint . '0';

	// split into groups by taking repeated substrings
	$start = 0;
	$groups = array();

	while ($start < strlen($fingerprint)) {
		array_push($groups, substr($fingerprint, $start, $group));
		$start = $start + $group;
	}

	return $groups;
}

function renderIcon($iconNumber, $icons) {
	$icon = $icons[intval($iconNumber)];

	$image = new Imagick('./templates/renderFrame.png');
	$iconImage = new Imagick('./icons/'.$icon->filename);
	$iconImage->scaleImage(100,100,true);
	$image->compositeImage($iconImage, Imagick::COMPOSITE_DARKEN, 6, 6);

	$draw = new ImagickDraw();
	$draw->setFillColor('black');
	$draw->setFont('Helvetica');
	$draw->setFontSize(16);
	$draw->setTextAlignment(Imagick::ALIGN_CENTER);
	
	$image->annotateImage($draw, 56, 130, 0, $iconNumber);
	
	$image->flattenImages();
	return $image;
}

function renderPadding() {
	$image = new Imagick('./templates/padding.png');
	return $image;
}

?>
