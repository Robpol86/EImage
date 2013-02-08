<?php
/**
 * Copyright (c) 2012, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageStaticAnnot {
    /*
     * Read user input and return HTML for an annotation. Designed to work with EImage but works in any <div /> with 
     * "position:relative;" in its style.
     * Heavily based on http://en.wikipedia.org/wiki/Template:Annotated_image
     *
     * @param Parser $parser: MediaWiki parser object.
	 * @param PPFrame $frame: The frame to use for expanding any template variables.
     * @param Array $args
     * @return Array
     */
    public static function annotation( Parser $parser, PPFrame $frame, $args ) {
        // Variables
        $x = 0; //Pixel displacement from left side of image (x coordinate) (required)
        $t = 0; //Pixel displacement from top side of image (origin is upper left) (required)
        $bg = 'transparent'; //Background color (default: transparent)
        $text = ''; //Annotation text to display (required)
        $size = null; //Font size
        $align = null; //Text alignment (useful for multi-line annotations)
        $style = null; //Font style
        $color = null; //Font color
        $family = null; //Font family
        $weight = null; //Font weight
        $shadow = null; //Font shadow(s)
        $height = null; //Line height

        // Read text position from user input (first argument).
        if ( preg_match( '/^([0-9]*)x([0-9]*)\s*(?:px)?\s*$/', array_shift( $args ), $m ) ) {
            // Copied from ./includes/parser/Parser.php
            $x = (int) $m[1];
            $t = (int) $m[2];
        }

        // Read the rest of the user input.
        foreach ( $args as $arg ) {
            $arg = trim( $frame->expand( $arg ) );
            $m = null;
            if ( EImageStatic::startsWith( $arg, 'bg=', $m ) ) { $bg = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'size=', $m ) ) { $size = (int) $m; continue; }
            if ( EImageStatic::startsWith( $arg, 'style=', $m ) ) { $style = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'color=', $m ) ) { $color = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'family=', $m ) ) { $family = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'weight=', $m ) ) { $weight = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'shadow=', $m ) ) { $shadow = htmlspecialchars( $m ); continue; }
            if ( EImageStatic::startsWith( $arg, 'height=', $m ) ) { $height = htmlspecialchars( $m ); continue; }
            if ( in_array( $arg, array( 'center', 'right', 'left', 'justify', 'inherit' ) ) ) {
                $align = $arg;
                continue;
            }
            $text = $parser->recursiveTagParse( $arg, $frame );
        }

        // Set <div /> style attribute.
        $div_style = "position:absolute; left:{$x}px; top:{$t}px;";
        if ( $size !== null ) $div_style .= " font-size:{$size}px;";
        if ( $align !== null ) $div_style .= " text-align:{$align};";
        if ( $style !== null ) $div_style .= " font-style:{$style};";
        if ( $family !== null ) $div_style .= " font-family:{$family};";
        if ( $weight !== null ) $div_style .= " font-weight:{$weight};";
        if ( $shadow !== null ) $div_style .= " text-shadow:{$shadow};";
        if ( $height !== null ) {
            $div_style .= " line-height:{$height};";
        } elseif ( $size !== null ) {
            $div_style .= " line-height:" . ($size + 2) . "px;";
        } else {
            $div_style .= " line-height:110%;";
        }

        // Set <span /> style attribute.
        $span_style = "background-color:{$bg};";
        if ( $color !== null ) $span_style .= " color:{$color};";

        // Build HTML.
        $html = Html::rawElement( 'div', array( 'style'=>$div_style ),
            Html::rawElement( 'span', array( 'style'=>$span_style ), $text )
        );

        // Encode output to eliminate padding placed by MediaWiki.
        return 'ENCODED_EIMAGE_CONTENT '.base64_encode( $html ).' END_ENCODED_EIMAGE_CONTENT';
    }
}
