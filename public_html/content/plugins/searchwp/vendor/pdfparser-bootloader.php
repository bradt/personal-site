<?php

// in order to accommodate for PHP 5.2 this needs to be abstracted to it's own file and conditionally included

if ( ! defined( 'ABSPATH' ) || ! defined( 'SEARCHWP_VERSION' ) ) exit;

class SearchWP_PdfParser {

	function init() {

		include_once( dirname( __FILE__ ) . '/tcpdf_parser.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Document.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Object.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Header.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Page.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Pages.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementBoolean.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementString.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementNumeric.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementArray.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementXRef.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementNull.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementDate.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementHexa.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementName.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementMissing.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Element/ElementStruct.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding/ISOLatin1Encoding.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding/ISOLatin9Encoding.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding/MacRomanEncoding.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding/StandardEncoding.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Encoding/WinAnsiEncoding.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font/FontCIDFontType0.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font/FontCIDFontType2.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font/FontTrueType.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font/FontType0.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Font/FontType1.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/XObject/Image.php' );
		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/XObject/Form.php' );

		include_once( dirname( __FILE__ ) . '/Smalot/PdfParser/Parser.php' );

		$parser = new \Smalot\PdfParser\Parser();
		return $parser;
	}

}
