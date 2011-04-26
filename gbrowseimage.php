<?php

// We should guard against direct invocation from the web for security
// reasons
if ( !defined( 'MEDIAWIKI' ) ) die();

/**
 *
 *
 */



// Extension configuration settings

$wgGBrowseImageScriptURL =
  'http://trimer.tamu.edu/cgi-bin/gbrowse_img';

$wgGBrowseImageGBrowseURL =
  'http://trimer.tamu.edu/cgi-bin/gbrowse';

$wgGBrowseImagePaddingLength = 1000;



// Register the extension details with Special:Version

$wgExtensionCredits['parserhook'][] = array(
  'path'           => __FILE__,
  'name'           => "GBrowseImage",
  'description'    => "Generates GBrowse images from a pre-configured GBrowse instance",
  'descriptionmsg' => "gbrowseimage-desc",
  'version'        => 0.2.1,
  'author'         => array('[mailto:bluecurio@gmail.com Daniel Renfro]', 'Jim Hu', 'Dan Bolser'),
  'url'            => "http://ecoliwiki.net",
);



// Three steps to link a specific function with a specific tag...

$wgHooks['ParserFirstCallInit'][] = 'efGBrowseImageInit';

function efGBrowseImageInit( &$parser ) {
  $parser->setHook( 'gbrowseimage', 'efGBrowseImageRender' );
  return true;
}

function efGBrowseImageRender( $input, $args, $parser, $frame ) {
  $gbi = new gbrowseImage( $input, $args, $parser, $frame );
  return $g->makeLink();
}



// Set up the class autoloader

$wgAutoloadClasses['gbrowseImage'] =
  dirname(__FILE__) . '/gbrowseimage.body.php';
