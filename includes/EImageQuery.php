<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageQuery {
    protected $parser; // MediaWiki parser object.
    protected $frame; // The frame to use for expanding any template variables.
    protected $data; // EImageData instance.
    protected $ei_image = null; // Image ID.
    protected $ei_host = null; // Image host.
    protected $wiki_title = null; // Base64 encoded title of the wiki article.
    protected $stale = null; // True if data in the DB is older than wgEImageStaleMinutes. Virgin image if null.

    function __construct( Parser $parser, PPFrame $frame, EImageData &$data, $image ) {
        $this->parser = $parser;
        $this->frame = $frame;
        $this->data = $data;
        $this->ei_image = $image;
        $this->wiki_title = base64_encode( $parser->getTitle()->getText() );
    }
    
    /*
     * Attempts to read cached data from the database for this image.
     */
    public function readDB() {
        if ( $this->ei_host === null ) return false;
        global $wgEImageStaleMinutes, $wgEImageTableName;
        
        // Attempt to read cached data from the database.
        $dbr = wfGetDB( DB_SLAVE );
        $res = $dbr->select(
            $wgEImageTableName, // table name (prefix automatically added)
            array( '*' ), // columns
            array( 'ei_host' => $this->ei_host, 'ei_image' => $this->ei_image ) // condition
        );
        $row = $dbr->fetchObject( $res );
        if ( ! $row ) return false; // No data in the database for this image.
        
        // Append this article's title to database for ArticlePurge hook.
        if ( strpos ( $row->ei_articles, $this->wiki_title ) === false ) {
            // This is the first time this page is using this image. Need to add it to the list in case user 
            // requests ?action=purge.
            $dbw = wfGetDB( DB_MASTER );
            $a = array( 'ei_articles' => $row->ei_articles . ' ' . $this->wiki_title );
            $dbw->update( $wgEImageTableName, $a, array(
                'ei_host'  => $this->ei_host, 
                'ei_image' => $this->ei_image
            ) );
        }
        
        // Load data into class instance.
        $this->data->set_ei_width( $row->ei_width );
        $this->data->set_ei_height( $row->ei_height );
        $this->data->set_ei_filename( $row->ei_filename );
        $this->data->set_ei_title( $this->parser, $this->frame, $row->ei_title );
        $this->data->set_ei_comment( $this->parser, $this->frame, $row->ei_comment );
        $this->data->set_ei_imgurlfs( $row->ei_imgurlfs );
        $this->data->set_ei_imgurlpage( $row->ei_imgurlpage );
        $this->data->set_ei_errormsg( $row->ei_errormsg );
        foreach ( (array) unserialize( $row->ei_imgurl ) as $width=>$url ) $this->data->add_ei_imgurl( $width, $url );
        
        // Check if DB data is fresh-enough or stale.
        $this->stale = ( time() < $row->ei_time_lu + ($wgEImageStaleMinutes * 60) ) ? false : true;
        
        return true;
    }
    
    /*
     * Queries the API of the image host.
     */
    public function queryAPI() {
        // This should be overriden by the child class.
        //if ( $this->ei_host === null || $this->stale === false ) return false;
        return $this->errorImage( wfMessage( 'eimage-invalidimageid' ) );
    }
    
    /*
     * Save data to database. This may be valid data, or the error placeholder. Reason for this is to prevent 
     * hammering the image host.
     * Referenced:
     *      http://svn.wikimedia.org/doc/bench__delete__truncate_8php_source.html
     *      http://www.librarywebchic.net/2006/05/08/extending-mediawiki/
     */
    public function writeDB() {
        if ( $this->ei_host === null || $this->stale === false ) return false;
        global $wgEImageTableName;

        // Build the data array to submit to the database.
        $new_data = array(
            'ei_width'      => $this->data->get_ei_width(),
            'ei_height'     => $this->data->get_ei_height(),
            'ei_filename'   => $this->data->get_ei_filename(),
            'ei_title'      => $this->data->get_ei_title(),
            'ei_comment'    => $this->data->get_ei_comment(),
            'ei_imgurl'     => serialize( $this->data->get_ei_imgurl() ),
            'ei_imgurlfs'   => $this->data->get_ei_imgurlfs(),
            'ei_imgurlpage' => $this->data->get_ei_imgurlpage(),
            'ei_errormsg'   => $this->data->get_ei_errormsg()
        );

        // Add data to $new_data depending if this will be an insert or an update.
        $search = array();
        if ( $this->stale === null ) { // This will be an insert.
            $new_data['ei_time_og'] = $new_data['ei_time_lu'] = time();
            $new_data['ei_image'] = $this->ei_image;
            $new_data['ei_host'] = $this->ei_host;
            $new_data['ei_articles'] = $this->wiki_title;
        } else {
            $new_data['ei_time_lu'] = time();
            $search = array( 'ei_host' => $this->ei_host, 'ei_image' => $this->ei_image );
        }

        // Write to database.
        $dbw = wfGetDB( DB_MASTER );
        $dbw->begin();
        if ( $this->stale === null ) $dbw->insert( $wgEImageTableName, $new_data );
        else $dbw->update( $wgEImageTableName, $new_data, $search );
        $dbw->commit();

        return true;
    }

    /*
     * Post-query cleanup such as replacing !!TITLE!! in captions with the actual title from the API, etc.
     *
     * @return Boolean: Always true.
     */
    public function cleanup() {
        $this->data->replaceFromHost();
        $this->data->linkJuggle();
        $this->data->amendWidth();
        $this->data->amendAltTitle();
        return true;
    }

    /*
     * Build HTML and return to parser. Output is encoded in the static method to work around the <p><br /></p>
     * problem. Borrowed from Widget extension.
     *
     * @return String: Final encoded HTML.
     */
    public function output() {
        $parsed_data = EImageStaticHtml::output( $this->data );
        return 'ENCODED_EIMAGE_CONTENT ' . base64_encode( $parsed_data ) . ' END_ENCODED_EIMAGE_CONTENT';
    }

    /*
     * Sets the error image.
     *
     * @param String $s: Error message to display.
     * @return Boolean: Always false.
     */
    public function errorImage( $s ) {
        global $wgEImageEmptyPng;
        $this->data->set_ei_width( 200 );
        $this->data->set_ei_height( 200 );
        $this->data->set_ei_errormsg( $s );
        $this->data->set_ei_filename( basename( $wgEImageEmptyPng ) );
        $this->data->set_ei_imgurlfs( $wgEImageEmptyPng );
        //$this->data->set_ei_title( $this->parser, $this->frame, $this->data->get_ei_errormsg() );
        //$this->data->set_ei_comment( $this->parser, $this->frame, $this->data->get_ei_errormsg() );
        $this->data->add_ei_imgurl( $this->data->get_ei_width(), $this->data->get_ei_imgurlfs() , true );
        return false;
    }
}
