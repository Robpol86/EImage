<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageQueryFlickr extends EImageQuery {
    protected $ei_host = 'flickr'; // Image host.
    
    /*
     * Queries the API of the image host.
     */
    public function queryAPI() {
        if ( $this->stale === false ) return false;
        $this->data->set_ei_errormsg( null );
        
        // Prepare POST vars.
        $url = 'http://api.flickr.com/services/rest/';
        $pvars = array(
            'api_key'           => '5c496ff1100bb27b583e1c4a15a5b86f',
            'photo_id'          => $this->ei_image,
            'format'            => 'json',
            'nojsoncallback'    => '1'
        );
        
        // flickr.photos.getSizes
        $json = array();
        $pvars['method'] = 'flickr.photos.getSizes';
        $status = EImageStatic::curl( $url, $pvars, $json );
        if ( $status !== 200 || !isset( $json['sizes']['size'][0]['width'] ) ) {
            // Error while accessing API.
            $errorMessage = wfMessage( 'eimage-flickrapierror', $status );
            if ( isset($json['code']) ) {
                $errorMessage .= '; ' . wfMessage( 'eimage-flickrapierrorcode', (int) $json['code'] );
            }
            if ( isset( $json['message'] ) ) $errorMessage .= '; ' . $json['message'];
            return $this->errorImage( $errorMessage );
        }
        foreach ( $json['sizes']['size'] as $size ) {
            if ( !isset( $size['label'] ) || !isset( $size['width'] ) || !isset( $size['height'] ) ) continue;
            if ( strpos( $size['label'], 'Square' ) !== false ) continue; //ignore sizes that ignore aspect ratio
            $this->data->add_ei_imgurl( $size['width'], $size['source'] );
            if ( $this->data->get_ei_width() === null || (int) $size['width'] > $this->data->get_ei_width() ) {
                // Flickr may or may not provide original image. Hence we need to choose the biggest they provide as 
                // our "original" size.
                $this->data->set_ei_width( $size['width'] );
                $this->data->set_ei_height( $size['height'] );
                $this->data->set_ei_imgurlfs( $size['source'] );
                $this->data->set_ei_filename( basename($size['source'] ) );
                $this->data->set_ei_imgurlpage( $size['url'] );
            }
        }
        
        // flickr.photos.getInfo
        $json = array();
        $pvars['method'] = 'flickr.photos.getInfo';
        $status = EImageStatic::curl( $url, $pvars, $json );
        if ( isset( $json['photo']['title']['_content'] ) ) {
            $this->data->set_ei_title( $this->parser, $this->frame, $json['photo']['title']['_content'] );
        }
        if ( isset( $json['photo']['description']['_content'] ) ) {
            $this->data->set_ei_comment( $this->parser, $this->frame, $json['photo']['description']['_content'] );
        }
        if ( isset( $json['photo']['urls']['url'][0]['_content'] ) ) {
            $this->data->set_ei_imgurlpage( $json['photo']['urls']['url'][0]['_content'] );
        }
        if ( $this->data->get_ei_title() !== null && $this->data->get_ei_comment() !== null ) return true;
        
        // flickr.photos.getExif
        $json = array();
        $pvars['method'] = 'flickr.photos.getExif';
        $status = EImageStatic::curl( $url, $pvars, $json );
        foreach ( $json['photo'] as $i ) {
            if ( $i['tag'] == 'Title' && $this->data->get_ei_title() === null ) {
                $this->data->set_ei_title( $this->parser, $this->frame, $i['raw']['_content'] );
            }
            if ( $i['tag'] == 'Description' && $this->data->get_ei_comment() === null ) {
                $this->data->set_ei_comment( $this->parser, $this->frame, $i['raw']['_content'] );
            }
        }
        
        return true;
    }

    /*
     * Sets the error image.
     *
     * @param String $s: Error message to display.
     * @return Boolean: Always false.
     */
    public function errorImage( $s ) {
        $this->data->set_ei_width( 500 );
        $this->data->set_ei_height( 374 );
        $this->data->set_ei_errormsg( $s );
        $this->data->set_ei_filename( 'photo_unavailable.gif' );
        $this->data->set_ei_imgurlfs( 'http://l.yimg.com/g/images/photo_unavailable.gif' );
        //$this->data->set_ei_title( $this->parser, $this->frame, $this->data->get_ei_errormsg() );
        //$this->data->set_ei_comment( $this->parser, $this->frame, $this->data->get_ei_errormsg() );
        $this->data->add_ei_imgurl( $this->data->get_ei_width(), $this->data->get_ei_imgurlfs() , true );
        $this->data->set_ei_imgurlpage( $this->data->get_ei_imgurlfs() );
        return false;
    }
}
