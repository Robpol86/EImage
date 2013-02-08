<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 *
 * This file holds the main class, the main entry point of EImage called by the MediaWiki when it encounters {{#eimage 
 * in the body of an article.
 */

class EImageStaticMain {
    /*
     * Read user input and return HTML.
     *
     * @param Parser $parser: MediaWiki parser object.
     * @param PPFrame $frame: The frame to use for expanding any template variables.
     * @param Array $args
     * @return String
     */
    public static function readInput( Parser $parser, PPFrame $frame, $args ) {
        // Prominent variables.
        $image = '';
        $host = '';
        $data = new EImageData;
        
        // Get image ID (API-supported image host) or image URL (raw mode) from first argument.
        $image = (string) array_shift( $args );
        
        // Automatically determine image host.
        if ( (string) filter_var( $image, FILTER_VALIDATE_URL ) !== '' ) $host = 'raw'; // $image is a URL.
        else {
            $valid_hosts = array(
                //'minus' => array( 'len' => array( 13, 13 ), 'regex' => '/[^a-zA-Z0-9]/' ),
                'flickr' => array( 'len' => array( 10, 10 ), 'regex' => '/[^0-9]/' ),
                'imgur' => array( 'len' => array( 5, 7 ), 'regex' => '/[^a-zA-Z0-9]/' ),
            );
            foreach ( $valid_hosts as $h => $conditions ) {
                $m = preg_replace( $conditions['regex'], '', $image );
                if ( $m !== $image ) continue; // Did not match regex condition.
                if ( strlen( $image ) < $conditions['len'][0] || strlen( $image ) > $conditions['len'][1] ) continue;
                // Detected image host successfully.
                $host = $h;
            }
        }

        // Parse the rest of the arguments.
        self::parseArgs( $parser, $frame, $data, $args );
        
        // Instantiate based on image host.
        $query = null;
        if ( $host == 'raw' ) $query = new EImageQueryRaw( $parser, $frame, $data, $image );
        elseif ( $host == 'imgur' ) $query = new EImageQueryImgur( $parser, $frame, $data, $image );
        elseif ( $host == 'flickr' ) $query = new EImageQueryFlickr( $parser, $frame, $data, $image );
        //elseif ( $host == 'minus' ) $query = new EImageQueryMinus( $parser, $frame, $data, $image );
        else $query = new EImageQuery( $parser, $frame, $data, $image );
        
        // Process data.
        $query->readDB();
        $query->queryAPI();
        $query->writeDB();
        $query->cleanup();
        
        // Generate HTML (encoded).
        return $query->output();
    }
    
    /*
     * Parses arguments from the user.
     *
     * @param Parser $parser: MediaWiki parser object.
     * @param PPFrame $frame: The frame to use for expanding any template variables.
     * @param EImageData $data: The EImageData object which holds user input and all other data about the image.
     * @param Array $args
     * @return Boolean: Always true.
     */
    public static function parseArgs( Parser $parser, PPFrame $frame, EImageData &$data, $args ) {
        foreach ( $args as $arg ) {
            $arg = (string) trim( $frame->expand( $arg ) );

            // Border and Inline
            if ( $arg == 'border' ) { $data->setBorderTrue(); continue; }
            if ( $arg == 'inline' ) { $data->setInlineTrue(); continue; }
        
            // Width and Height
            $m = null;
            if ( preg_match( '/^([0-9]*)x([0-9]*)\s*(?:px)?\s*$/', $arg, $m ) ) {
                // Copied from ./includes/parser/Parser.php
                $data->setWidth( $m[1] );
                $data->setHeight( $m[2] );
                continue;
            }
            if ( preg_match( '/^[0-9]*\s*(?:px)?\s*$/', $arg ) ) { $data->setWidth( $arg ); continue; }
            
            // Anything with an equals sign.
            $m = null;
            if ( EImageStatic::startsWith( $arg, 'alt=', $m ) ) { $data->setAlt( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'link=', $m ) ) { $data->setLink( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'abg=', $m ) ) { $data->setABg( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'asize=', $m ) ) { $data->setASize( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'aalign=', $m ) ) { $data->setAAlign( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'astyle=', $m ) ) { $data->setAStyle( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'acolor=', $m ) ) { $data->setAColor( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'afamily=', $m ) ) { $data->setAFamily( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'aweight=', $m ) ) { $data->setAWeight( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'ashadow=', $m ) ) { $data->setAShadow( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'aheight=', $m ) ) { $data->setAHeight( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'annot=', $m ) ) { $data->addAnnot( $parser, $frame, $m ); continue; }

            // Everything else.
            if ( $data->setFormat( $arg ) ) continue;
            if ( $data->setHAlign( $arg ) ) continue;
            if ( $data->setVAlign( $arg ) ) continue;
            $data->setCaption( $parser, $frame, $arg );
        }
        
        return true;
    }
}
