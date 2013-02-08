<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageQueryImgur extends EImageQuery {
    protected $ei_host = 'imgur'; // Image host.
    
    /*
     * Queries the API of the image host.
     */
    public function queryAPI() {
        if ( $this->stale === false ) return false;
        $this->data->set_ei_errormsg( null );
        
        // Query host. v3 API is kinda buggy and randomly issues HTTP 0 or 400.
        $url = "https://api.imgur.com/3/image/{$this->ei_image}.json";
        $header = array( 'Authorization: Client-ID 9dbe77ac233623c' );
        for ( $i = 0; $i < 3; $i++ ) {
            $json = array();
            $status = (int) EImageStatic::curl( $url, array(), $json, $header );
            if ( $status != 0 && $status != 400) break;
            sleep( 1 );
        }
        
        // Check for errors.
        $errorMessage = null;
        if ( $status === 403 ) {
            $errorMessage = '; ' . (string) wfMessage( 'eimage-imgurapierrorcons' );
        } elseif ( $status !== 200 ) {
            $errorMessage = ''; // No special message, just the default API HTTP error message below.
        } elseif ( !isset( $json['data']['width'] ) ) {
            $errorMessage = '; ' . (string) wfMessage( 'eimage-imgurapierrorjson' );
        } elseif ( (int) $json['data']['width'] < 1 ) {
            $errorMessage = '; ' . (string) wfMessage( 'eimage-imgurzerowidth' );
        }
        if ( $errorMessage !== null ) {
            // Looks like we had a problem.
            return $this->errorImage( wfMessage( 'eimage-imgurapierror', $status ) . $errorMessage );
        }
        
        // Parse JSON response from image host.
        $this->data->set_ei_width( $json['data']['width'] );
        $this->data->set_ei_imgurlfs( "http://i.imgur.com/{$this->ei_image}.gif" );
        $this->data->add_ei_imgurl( $json['data']['width'], $this->data->get_ei_imgurlfs(), true );
        $this->data->set_ei_filename( basename( $this->data->get_ei_imgurlfs() ) );
        $this->data->set_ei_imgurlpage( "http://imgur.com/{$this->ei_image}" );
        if ( isset( $json['data']['height'] ) ) $this->data->set_ei_height( $json['data']['height'] );
        if ( isset( $json['data']['title'] ) )
            $this->data->set_ei_title( $this->parser, $this->frame, $json['data']['title'] );
        if ( isset( $json['data']['description'] ) )
            $this->data->set_ei_comment( $this->parser, $this->frame, $json['data']['description'] );
        foreach ( array( 160=>'t', 320=>'m', 640=>'l', 1024=>'h' ) as $k=>$v ) {
            if ( $json['data']['width'] > $k )
                $this->data->add_ei_imgurl( $k, "http://i.imgur.com/{$this->ei_image}{$v}.jpg" );
        }

        return true;
    }
}
