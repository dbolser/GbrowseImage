<?php

/**
 *	gbrowse_img.php  	           2010-07-19   Daniel Renfro <bluecurio@gmail.com>
 *
 *  This extension will take a set of parameters wrapped in a set of <gbrowseImage>
 *	html-style tags and create a clickable picture.
 *
 *
 *  The options are parsed and then added to a $query variable which is then
 *	transformed into an HTTP query to gbrowse_img.
 *
 *
 *  See also:
 *	  * http://www.wormbase.org/db/seq/gbrowse_img
 *
 */

$wgExtensionCredits['parserhook'][] = array(
  'author'      => array('[mailto:bluecurio@gmail.com Daniel Renfro]', 'Jim Hu'),
  'description' => 'gbrowse images',
  'name'        => 'gbrowseImage',
  'update'      => '2010-07-19',
  'url'         => 'http://ecoliwiki.net',
  'status'      => 'development',
  'type'        => 'hook',
  'version'     => '0.2'
);

//Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'efGbrowseImageSetup';
} else { // Otherwise do things the old fashioned way
	$wgExtensionFunctions[] = "efGbrowseImageSetup";
}

// hookup the object/method to the tag
function efGbrowseImageSetup() {
	global $wgParser;
	$wgParser->setHook( "gbrowseImage", 'efGbrowseImageRender' );
	return true;
}

// have to wrap the object in a function to get MW to execute it properly.
function efGbrowseImageRender( $input, $args, &$parser, $frame = null ) {
	$g = new gbrowseImage($input, $args, $parser, $frame ) ;
	return $g->makeLink();
}


class gbrowseImage {

	// create a constant to pad the coordinates
	const PADDING_LENGTH = 1000;

	// where the gbrowse_img script lives on the web
    const GBROWSE_IMG = 'http://heptamer.tamu.edu/cgi-bin/gb2/gbrowse_img';

	// where gbrowse lives
    const GBROWSE = 'http://heptamer.tamu.edu/cgi-bin/gb2/gbrowse';


	var $input;						// stuff between the tags
	var $args;						// xml-style arguments (within the opening tag)

	// various settings
	var $preset;       				// used to distinguish between different types of preset configuations (presets)
	var $caption;					// wikitext to use as a caption (probably a transclusion)
	var $query = array();			// will hold all the options for gbrowse_img, assoc. array

	// gbrowse_img arguments
	var $source;					// the source of the data, never null
	var $name;						// genomic landmark or range
	var $coordinates;    			// an array, order dependent, possibly null
	var $type;						// tracks to include in image
	var $width; 				    // desired width of image
	var $options;					// list of track options (compact, labeled, etc)
	var $abs;						// display position in absolute coordinates
	var $add;						// added feature(s) to superimpose on the image
	var $style;						// stylesheet for additional features
	var $keystyle;					// where to place the image key
	var $overview;					// force an overview-style display
	var $flip;              		// bool, whether to reverse the image or not
	var $grid;						// turn grid on (1) or off (0)
	var $embed;						// generate full HTML for image and imagemap for use in an embedded frame
	var $format;					// format for the image (use "SVG" for scaleable vector graphics)



	public function __construct( $input, $args, $parser, $frame = null ) {
		$this->input = $input;
		$this->args = $args;
		// parser not used
		// frame not used
	}

	protected function parseInput( $input ) {

		// parse the options in $input
		$input = trim($input);
		$lines_of_input = explode("\n", $input);
		foreach ( $lines_of_input as $line ) {

			// key=value pairs
			if (preg_match('/^([^=]*?)\s*=\s*(.*)$/', $line, $m) ) {
				$option = trim($m[1]);
				$value  = trim($m[2]);

				switch ( $option ) {
					case "source":
						$this->source = $value;
						 break;
					case "name":
						$this->name = $value;
						break;
					case "width":
						// get the integer value of whatever came in
                 		$this->width = intval( $value );
						break;
					case "type":
						$this->type = $value;
						break;
					case 'options':
						$this->options = $value;
						break;
					case 'abs':
						$this->abs = $value;
						break;
					case 'add':
						$this->add = $value;
						break;
					case 'style':
						$this->style = $value;
						break;
					case 'keystyle':
						$this->keystyle = $value;
						break;
					case 'overview':
						$this->overview = $value;
						break;
					case 'grid':
						$this->grid = $value;
						break;
					case 'format':
						$this->format = $value;
						break;
					case "embed":
						$this->embed = $value;
						break;
					case "flip":
						// if there is anything that evaluates to true, use it
						if ( $value ) {
							$this->flip = true;
						}
						break;
					case "preset":
                    	$this->preset = $value;
						 break;
					case 'caption':
						// could be problematic because of multiple lines....oh well.
						$this->caption = $value;
						break;
				}
			}
		}
	}	// done parsing parameters

	public function makeLink() {

		wfProfileIn( __METHOD__ );

		// get the options from between the tags
		$this->parseInput( $this->input );

		// check for required parameters
		if ( !isSet($this->source) ) {
			trigger_error( 'no source set, cannot continue', E_USER_WARNING );
			return $this->makeErrorString( 'No \'source\' parameter set. Cannot make image.' );
		}
		if ( !isSet($this->name) ) {
			trigger_error( 'no name set, cannot continue', E_USER_WARNING );
			return $this->makeErrorString( 'No \'name\' parameter set. Cannot make image.' );
		}

		// set up the basic/default options for gbrowse_img into an array
		$this->query['name'] = $this->name;
		$this->query['width'] 	= ( isSet($this->width) && $this->width )
			? $this->width
			: 300;
		$this->query['type'] 	= ( isSet($this->type) && $this->type )
			? $this->type
			: "Genes+Genes:region+ncRNA+ncRNA:region";

		if ( isSet($this->options) ) {
			$this->query['options'] = $this->options;
		}
		if ( isSet($this->abs) ) {
			$this->query['abs'] = $this->abs;
		}
		if ( isSet($this->add) ) {
			$this->query['add'] = $this->add;
		}
		if ( isSet($this->style) ) {
			$this->query['style'] = $this->style;
		}
		if ( isSet($this->keystyle) ) {
			$this->query['keystyle'] = $this->keystyle;
		}
		if ( isSet($this->flip) ) {
			$this->query['flip'] = $this->flip;
		}
		if ( isSet($this->grid) ) {
			$this->query['grid'] = $this->grid;
		}
		if ( isSet($this->embed) ) {
			$this->query['embed'] = $this->embed;
		}
		if ( isSet($this->format) ) {
			$this->query['format'] = $this->format;
		}

		//  check if we're serving up a preset, overwrite any settings with these
		if ( isSet($this->preset) && $this->preset ) {
			switch ( $this->preset ) {
			    case "GeneLocation":
			    	// pad the figure with a set amount on 5' and 3' ends
			    	$padding_amount = 1000; //  1kb nt
			 		list($landmark, $coordA, $coordB) = $this->parseLandmark( $this->name );
					// don't go further than the origin on the 5' side
					if ( $coordA - $padding_amount < 0 ) {
						$coordA = 0;
					}
					else {
						$coordA -= $padding_amount;
					}
					$coordB += $padding_amount;
					// reconstruct the name parameter
					$this->query['name'] = sprintf('%s:%d..%d', $landmark, $coordA, $coordB );
			        break;
			    case "Nterminus":
			    	// we have to turn flip on/off explicitly in the parameters.
			    	// GBrowse allows low->high coordinates to be on the minus strand.
			    	// i.e. ( high->low != 'minus strand' )
			    	$this->query['name'] = $this->name;
			    	$this->query['type'] = 'Gene+DNA_+Protein';
			    	$this->query['width'] = 400;
			    	break;
			    case 'SubtilisQuickView_xy':
					// This is for the new quickview on the subtiliswiki
					$this->query['name'] = $this->name;
					$this->query['type'] =  'Rasmussen_xy';
					$this->query['wdith'] = 500;
					break;
				case 'SubtilisQuickView_xy_LB_genes':
					$this->query['name'] = $this->name;
					$this->query['type'] =  'Genes+Rasmussen_xy_LB';
					$this->query['wdith'] = 500;
					break;
	            case 'SubtilisQuickView_density':
					// This is for the new quickview on the subtiliswiki
					$this->query['name'] = $this->name;
					$this->query['type'] =  'Rasmussen_density';
					$this->query['wdith'] = 500;
					break;
				 case 'SubtilisQuickView_genes':
					// This is for the new quickview on the subtiliswiki
					$this->query['name'] = $this->name;
					$this->query['type'] =  'Genes';
					$this->query['wdith'] = 500;
					break;
	            default:
			    	// do nothing.
			        break;
			}
		}		// make the HTML
		$html = '<a href="' . $this->makeGbrowseURL() . '" target="_blank">
		            <img src="' . $this->makeGbrowseImgURL() . '" alt="' . $this->makeGbrowseURL() . '" />
		        </a>';

		// for debugging
		#$html .= '<br />' . htmlentities($this->makeGbrowseImgURL());

		$html .= ( isSet($this->caption) && $this->caption )
			? "\n" . $this->caption
			: "";

		wfProfileOut( __METHOD__ );

		return $html . "\n";
	}
	
	
	
	// EcoliWiki's Gbrowse2 doesn't like to have plus-signs (+) in the type paramter in 
	//   the URL, so let's kludge it by adding multiple "&type="'s. 
	//
	// The http_build_query() function will add the first "type=", let's take care of the rest...
	// 
	protected function formatTypeParameter() {
		$tracks = explode( '+', $this->query['type'] );
		$string = "";
		for ( $i=0, $c=count($tracks); $i<$c; $i++ ) {
			if ( $i != 0 ) {
				$string .= '&type=';
			}
			$string .= $tracks[$i];
		}
		$this->query['type'] = $string;
			
	}
	


	// use like this:    list($chromosome, $coordA, $coordB) = $this->parseLandmark( $landmark );
	protected function parseLandmark( $name ) {
		if ( preg_match( '/(.*):(\d+)\.\.(\d+)/', $name, $m ) ) {
			return array_splice( $m, 1, 3 ); // return $m[1..3]
		}
		else {
			return false;
		}
	}

	protected function makeGbrowseImgURL() {
		$base = self::GBROWSE_IMG . '/' . $this->source;
		$this->formatTypeParameter();
		$url = $base . '/?' . http_build_query( $this->query );
		return urldecode($url);
	}

	protected function makeGbrowseURL() {
		$base = self::GBROWSE . '/' . $this->source;
		$url = $base . '/?name=' . $this->name;
		return urldecode($url);
	}

	protected function makeErrorString( $message ) {
		return '<pre style="color:red;">gbrowseImage error:' . "\n  " . $message . '</pre>';
	}

}

