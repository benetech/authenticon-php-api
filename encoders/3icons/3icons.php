<?php
define('EXPIRATION', 86400);	// 86,400 seconds = 24 hours

header('Access-Control-Allow-Origin: *');

$fingerprint = $_GET['fingerprint'];
$part = $_GET['part'];
$textOnly = $_GET['textOnly'];

$icons = (array)json_decode(file_get_contents("./icons.json"));

$mem = new Memcached();
$mem->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
$mem->addServer('127.0.0.1', 11211);

$iconNames = array();

if ($fingerprint == null) {
	// no fingerprint, so return all words/icons
	$iconNames = array_keys($icons);
} else {
	$iconNames = getIconNamesForFingerprint($fingerprint, $icons, $mem);
}

if ($textOnly == true) {
	header('Content-type: text/plain');
	$text = '';
	if ($part == null)
		foreach($iconNames as $iconName)
			$text = $text . $iconName . "\n";
	else
		$text = $iconNames[$part];

	echo $text;
} else {
	$image = new Imagick();

        if ($part == null)
		foreach($iconNames as $iconName)
			$image->addImage(renderIcon($iconName, $icons));
                else
                        $image->addImage(renderIcon($iconNames[$part], $icons));

	$image->resetIterator();
	$combined = $image->appendImages(false);
	$combined->setImageFormat('png');

	header('Content-type: image/png');
	echo $combined;
}

// ----------------------------------------------------------------------------

function getIconNamesForFingerprint($fingerprint, $icons, $mem) {
	$iconNames = $mem->get($fingerprint);
	if (!$iconNames) {
		do {
			$iconNames = getRandomIconNames(3, $icons);
			if ($mem->add($iconNames, $fingerprint, EXPIRATION) == false)
				$iconNames = false;	// it's already in use
		} while ($iconNames == false);
		if ($mem->add($fingerprint, $iconNames, EXPIRATION) == false)
			$iconNames = $mem->get($fingerprint);	// someone else created it, so use that instead
	}
	$mem->touch($fingerprint, EXPIRATION);	// reset expiration timeout
	$mem->touch($iconNames, EXPIRATION);	
	return explode('-', $iconNames);
}

function getRandomIconNames($number, $icons) {
	$iconNames = array_keys($icons);
	$randomIconNames = '';
	do {
		$nextRandomIconName = '';
		do {
			$nextRandomIconName = $iconNames[rand(0, count($iconNames)-1)];
		} while (strpos($randomIconNames, $nextRandomIconName) !== false);
		$randomIconNames = $randomIconNames . $nextRandomIconName;
		$number = $number - 1;
		if ($number > 0)
			$randomIconNames = $randomIconNames . '-';
	} while ($number > 0);
	return $randomIconNames;
}

function renderIcon($iconName, $icons) {
	$icon = $icons[$iconName];
	$image = new Imagick('./templates/renderFrame.png');
	$iconImage = new Imagick('./icons/'.$icon->filename);
	$iconImage->scaleImage(120,120,true);
	$image->compositeImage($iconImage, Imagick::COMPOSITE_DARKEN, 15, 15);

	$draw = new ImagickDraw();
	$draw->setFillColor('black');
	$draw->setFont('Helvetica');
	$draw->setFontSize(25);
	$draw->setTextAlignment(Imagick::ALIGN_CENTER);
	
	$image->annotateImage($draw, 75, 185, 0, $iconName);

	$draw->setFontSize(12);
	$draw->setTextAlignment(Imagick::ALIGN_LEFT);
	$creditColor = new ImagickPixel('graya(0%, 0.2)');
	$draw->setFillColor($creditColor);
	
	$words = explode(' ', $icon->credit);
	$credit = '';
	$currentLine = '';
	foreach($words as $word) {
		if ($currentLine == '') {
			$credit = $credit . $word . ' ';
			$currentLine = $word;
		}
		else {
			$currentLine = $currentLine . $word;
			$textWidth = $image->queryFontMetrics($draw, $currentLine)['textWidth'];
			if ($textWidth > 130) {
				$credit = $credit . "\n";
				$currentLine = $word;
			} else
				$currentLine = $currentLine . ' ';
			$credit = $credit .  $word . ' ';
		}
	}

	$image->annotateImage($draw, 10, 220, 0, $credit);

	$image->flattenImages();
	return $image;
}

?>
