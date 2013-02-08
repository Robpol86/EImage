<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageStaticHtml {
    /*
     * Handles building the HTML for framed images (thumbnails and frames).
     *
     * @param String $prefix
     * @param String $postfix
     * @param String $hAlign
     * @param String $imgTag
     * @param String $format
     * @param EImageData $data: The EImageData object which holds user input and all other data about the image.
     * @return String: Entire HTML text for this EImage instance.
     */
    public static function framedImage( $prefix, $postfix, $hAlign, $imgTag, $format, EImageData &$data ) {
        global $wgContLang;
        if ( $hAlign === null ) $hAlign = $wgContLang->alignEnd();
        $zoomIcon = '';
        $outerWidth = $data->getWidth() + 2;

        // Outer divs.
        if ( $data->getInline() !== null ) {
            $prefix .= "<div class=\"thumb t{$hAlign}\" style=\"display:inline-block;\">";
        } else {
            $prefix .= "<div class=\"thumb t{$hAlign}\">";
        }
        $prefix .= "<div class=\"thumbinner\" style=\"width:{$outerWidth}px;\">";
        $postfix = "</div></div>{$postfix}";

        // Zoom icon. From ./includes/Linker.php
        if ( $format == 'thumb' ) {
            global $wgStylePath;
            $zoomIcon = Html::rawElement( 'div', array( 'class'=>'magnify' ),
                Html::rawElement( 'a', array(
                    'href'  => $data->get_ei_imgurlfs(),
                    'class' => 'internal',
                    'title' => wfMessage( 'eimage-thumbnail-more' ) ),
                    Html::element( 'img', array(
                        'src'   => $wgStylePath.'/common/images/magnify-clip'.($wgContLang->isRTL()?'-rtl':'').'.png',
                        'width' => 15,
                        'height'=> 11,
                        'alt'   => "" ) ) ) );
        }

        // Caption (including zoom icon).
        $caption = "<div class=\"thumbcaption\">{$zoomIcon}" . $data->getCaption() . "</div>";

        // Done!
        return "{$prefix}{$imgTag}{$caption}{$postfix}";
    }

    /*
     * Takes in a EImageData object (which includes image metadata from the database and/or from queries to image host 
     * APIs) and returns the HTML to be displayed to the user.
     *
     * @param EImageData $data: The EImageData object which holds user input and all other data about the image.
     * @return String: Entire HTML text for this EImage instance.
     */
    public static function output( EImageData &$data ) {
        $prefix = $postfix = '';
        $hAlign = $data->getHAlign();
        $format = $data->getFormat();

        // Center align. From ./includes/Linker.php
        if ( $hAlign == 'center' ) {
            $prefix  = '<div class="center">';
            $postfix = '</div>';
            $hAlign = 'none';
        }

        // Img tag.
        $imgTag = array(
            'src'   => $data->getBestImgUrl(), // Initially sets the biggest image.
            'width' => $data->getWidth()
        );
        if ( $data->getAlt() !== null ) $imgTag['alt'] = $data->getAlt();
        if ( $data->getTitle() !== null ) $imgTag['title'] = $data->getTitle();
        if ( in_array( $format, array( 'frame', 'thumb' ) ) ) {
            $imgTag['class'] = 'thumbimage';
        } else {
            if ( $data->getVAlign() !== null ) $imgTag['style'] = 'vertical-align: ' . $data->getVAlign();
            if ( $data->getBorder() !== null ) $imgTag['class'] = 'thumbborder';
        }
        $imgTag = Html::element( 'img', $imgTag );
        if ( $data->getLink() !== null ) {
            $imgTag = "<a href=\"" . $data->getLink() . "\" class=\"image\">{$imgTag}</a>";
        }

        // Annotations.
        $annot = $data->getAnnot();
        if ( $data->get_ei_errormsg() !== null ) {
            // Display error messages as annotations over the image.
            $e = Html::rawElement( 'div', 
                array( 'style' => 'position:absolute; left:1px; top:1px; font-size:15px; color:red;' ), 
                $data->get_ei_errormsg() 
            );
            array_push( $annot, $e );
        }
        if ( !empty( $annot ) ) {
            // Apply defaults.
            $div_style = "background-color:" . $data->getABg() . ";";
            if ( $data->getASize() !== null ) $div_style .= " font-size:" . $data->getASize() . "px;";
            if ( $data->getAAlign() !== null ) $div_style .= " text-align:" . $data->getAAlign() . ";";
            if ( $data->getAStyle() !== null ) $div_style .= " font-style:" . $data->getAStyle() . ";";
            if ( $data->getAFamily() !== null ) $div_style .= " font-family:" . $data->getAFamily() . ";";
            if ( $data->getAWeight() !== null ) $div_style .= " font-weight:" . $data->getAWeight() . ";";
            if ( $data->getAShadow() !== null ) $div_style .= " text-shadow:" . $data->getAShadow() . ";";
            if ( $data->getAColor() !== null ) $div_style .= " color:" . $data->getAColor() . ";";
            if ( $data->getAHeight() !== null ) {
                $div_style .= " line-height:" . $data->getAHeight() . ";";
            } elseif ( $data->getASize() !== null ) {
                $div_style .= " line-height:" . ($data->getASize() + 2) . "px;";
            } else {
                $div_style .= " line-height:110%;";
            }

            // Insert <img /> (including <a />) into <div /> and annotations after <img /> or <a />.
            $imgTag = Html::rawElement( 'div', 
                array( 'style' => 'position:relative; display:inline-block; overflow:hidden; ' . $div_style ), 
                $imgTag . implode( '', $annot ) 
            );
        }
        
        // Frames and Thumbs.
        if ( in_array( $format, array( 'frame', 'thumb' ) ) ) {
            return self::framedImage( $prefix, $postfix, $hAlign, $imgTag, $format, $data );
        }

        // Frameless and Unspecified. Only use <div /> if alignment is specified.
        if ($hAlign !== null ) {
            $prefix .= "<div class=\"float{$hAlign}\">";
            $postfix = "</div>{$postfix}";
        }
        return "{$prefix}{$imgTag}{$postfix}";
    }
}
