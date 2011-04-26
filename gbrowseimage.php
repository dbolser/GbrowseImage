<?php

  // Guard against direct invocation from the web for security reasons
if ( !defined( 'MEDIAWIKI' ) ) die();

/**
 * Header go here
 *
 */



// Extension configuration settings

// The location of the gbrowse_img script
$wgGbrowseImageScriptURL =
  'http://heptamer.tamu.edu/cgi-bin/gb2/gbrowse_img';

// The location of the acompanying gbrowse
$wgGbrowseImageGbrowseURL =
  'http://heptamer.tamu.edu/cgi-bin/gb2/gbrowse';

// The default padding (in bases)
$wgGbrowseImagePaddingLength = 1000;

// The default image size (in px)
$wgGbrowseImageSize = 300;




// Register the extension details with Special:Version

$wgExtensionCredits['parserhook'][] =
  array(
	'path'           => __FILE__,
	'name'           => "GbrowseImage",
	'description'    => "Generates Gbrowse images from a pre-configured Gbrowse instance",
	'descriptionmsg' => "gbrowseimage-desc",
	'version'        => 0.2.1,
	'author'         => array('[mailto:bluecurio@gmail.com Daniel Renfro]', 'Jim Hu'),
	'url'            => "http://ecoliwiki.net",
	);



// Three steps to link a specific function with a specific tag...

$wgHooks['ParserFirstCallInit'][] = 'efGbrowseImageInit';

function efGbrowseImageInit( &$parser ) {
  $parser->setHook( 'gbrowseimage', 'efGbrowseImageRender' );
  return true;
}

function efGbrowseImageRender( $input, $args, $parser, $frame ) {
  $gbi = new gbrowseImage( $input, $args, $parser, $frame );
  return $gbi->makeLink();
}



// Set up the class autoloader

$wgAutoloadClasses['gbrowseImage'] =
  dirname(__FILE__) . '/gbrowseimage.body.php';
