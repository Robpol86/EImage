<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageQueryRaw extends EImageQuery {
    protected $ei_host = 'raw';

    /*
     * Database not used for raw mode.
     */
    public function readDB() {
        return true;
    }
    
    /*
     * Sets image metadata.
     */
    public function queryAPI() {
        if ( $this->data->getWidth() === null ) return $this->errorImage( wfMessage( 'eimage-rawmissingwidth' ) );
        if ( $this->data->getHeight() === null ) return $this->errorImage( wfMessage( 'eimage-rawmissingheight' ) );
        $this->data->set_ei_errormsg( null );
        $this->data->set_ei_width( $this->data->getWidth() );
        $this->data->set_ei_height( $this->data->getHeight() );
        $this->data->add_ei_imgurl( $this->data->get_ei_width(), $this->ei_image );
        return true;
    }
    
    /*
     * Database not used for raw mode.
     */
    public function writeDB() {
        return true;
    }
}
