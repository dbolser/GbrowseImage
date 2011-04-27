<?php

class gbrowseImage {
  
  // stuff between the tags
  var $input;
  
  // xml-style arguments (within the opening tag)
  var $args;
  
  
  // various settings
  
  // configuations presets
  var $preset;
  
  // wikitext to use as a caption (probably a transclusion)
  var $caption;
  
  // gbrowse_img arguments
  var $gbArgs = array();
  
  
  
  // Construct the class
  public function __construct( $input, $args, $parser, $frame = null ) {
    $this->input = $input;
    $this->args = $args;
    // parser not used
    // frame not used
  }
  
  
  // parse the options in $input
  protected function parseInput( $input ) {
    
    $input = trim($input);
    $lines_of_input = explode("\n", $input);
    
    foreach ( $lines_of_input as $line ) {
      
      // key=value pairs
      if (preg_match('/^([^=]*?)\s*=\s*(.*)$/', $line, $m) ) {
	$option = trim($m[1]);
	$value  = trim($m[2]);
	
	$this->$gbArgs[$option] = $value;
      }
    }
  }	// done parsing parameters
  
  
  
  // Turn passed options into a 'gbrowse_img' URL
  public function makeLink() {
    
    wfProfileIn( __METHOD__ );
    
    // get the options from between the tags
    $this->parseInput( $this->input );
    
    // check for required parameters
    if ( !isSet($this->gbArgs['source']) ) {
      trigger_error( 'no source set, cannot continue', E_USER_WARNING );
      return $this->makeErrorString( 'No \'source\' parameter set. Cannot make image.' );
    }
    
    if ( !isSet($this->gbArgs['name']) ) {
      trigger_error( 'no name set, cannot continue', E_USER_WARNING );
      return $this->makeErrorString( 'No \'name\' parameter set. Cannot make image.' );
    }
    
    $this->gbArgs['width'] = ( isSet($this->gbArgs['width']) && $this->gbArgs['width'] )
      ? $this->gbArgs['width']
      : $wgGbrowseImageSize;
    
    // Setting a default type is ... tricky... Would be nice to query
    // the script for types if no user-type is supplied
    $this->gbArgs['type'] = ( isSet($this->gbArgs['type']) && $this->gbArgs['type'] )
      ? $this->gbArgs['type']
      : "Genes+Genes:region+ncRNA+ncRNA:region";
    



    // USE htmlspecialchars() HERE!

     // make the HTML
    $html = '<a href="' . $this->makeGbrowseURL() . '" target="_blank">
		            <img src="' . $this->makeGbrowseImgURL() . '" alt="' . $this->makeGbrowseURL() . '" />
		        </a>';
    
    // for debugging
    #$html .= '<br />' . htmlentities($this->makeGbrowseImgURL());
    
    $html .= ( isSet($this->gbArgs['caption']) && $this->gbArgs['caption'] )
      ? "\n" . $this->gbArgs['caption']
      : "";
    
    wfProfileOut( __METHOD__ );
    
    return $html . "\n";
  }
  
  
  
  // EcoliWiki's Gbrowse2 doesn't like to have plus-signs (+) in the
  // type paramter in the URL, so let's kludge it by adding multiple
  // "&type="'s.

  //
  // The http_build_query() function will add the first "type=", let's
  // take care of the rest...
  // 
  protected function formatTypeParameter() {
    $tracks = explode( '+', $this->gbArgs['type'] );
    $string = "";
    for ( $i=0, $c=count($tracks); $i<$c; $i++ ) {
      if ( $i != 0 ) {
	$string .= '&type=';
      }
      $string .= $tracks[$i];
    }
    $this->gbArgs['type'] = $string;

  }
  
  
  protected function makeGbrowseImgURL() {
    $base = $wgGbrowseImageScriptURL . '/' . $this->gbArgs['source'];
    $this->formatTypeParameter();
    $url = $base . '/?' . http_build_query( $this->gbArgs );
    return urldecode($url);
  }
  
  protected function makeGbrowseURL() {
    $base = $wgGbrowseImageGbrowseURL . '/' . $this->gbArgs['source'];
    $url = $base . '/?name=' . $this->name;
    return urldecode($url);
  }
  
  protected function makeErrorString( $message ) {
    return '<pre style="color:red;">gbrowseImage error:' . "\n  " . $message . '</pre>';
  }
  
}
