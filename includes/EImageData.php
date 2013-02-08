<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageData {
    // Image host/database related properties.
    private $ei_width = null; // image width provided by host API
    private $ei_height = null; // image height
    private $ei_filename = null; // image filename
    private $ei_title = null; // image title (metadata)
    private $ei_comment = null; // image description/comment (metadata)
    private $ei_imgurl = array(); // displayed image url as an array with widths as keys
    private $ei_imgurlfs = null; // url to full size image
    private $ei_imgurlpage = null; // url to image page (image host's web page with options/etc)
    private $ei_errormsg = null; // if an error occurred accessing an API, this will have a message

    // Local display related properties.
    private $width = null; // requested width
    private $height = null; // requested height
    private $link = null; // url when clicking image
    private $alt = null; // <img alt='' />
    private $title = null; // <img title='' />
    private $caption = null; // caption below thumb/frame
    private $format = null; // thumb, frame, frameless
    private $border = null; // true or null
    private $hAlign = null; // center, right, left, none
    private $inline = null; // true or null
    private $vAlign = null; // baseline, sub, super, top, text-top, middle, bottom, text-bottom

    // Annotation defaults properties. Overrides browser defaults for all annotations on an image.
    private $aAlign = null; // text alignment
    private $aBg = null; // background color
    private $aFamily = null; // font family
    private $aSize = null; // font size
    private $aWeight = null; // font weight
    private $aStyle = null; // font style
    private $aShadow = null; // font shadow
    private $aColor = null; // font color
    private $aHeight = null; // line height

    // Annotation properties.
    private $annot = array(); // array of strings

    // Set host related properties.
    public function set_ei_width( $i ) {
        $i = (int) $i;
        if ( $i < 1 ) $this->ei_width = null;
        else $this->ei_width = $i;
        return true;
    }

    public function set_ei_height( $i ) {
        $i = (int) $i;
        if ( $i < 1 ) $this->ei_height = null;
        else $this->ei_height = $i;
        return true;
    }

    public function set_ei_filename( $s ) {
        if ( $s === null ) {
            $this->ei_filename = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->ei_filename = $s;
            return true;
        }
        return false;
    }

    public function set_ei_title( $parser, $frame, $s ) {
        if ( $s === null ) {
            $this->ei_title = null;
            return true;
        }
        $this->ei_title = (string) $parser->recursiveTagParse( EImageStatic::href( nl2br ( trim ( $s ) ) ), $frame );
        return true;
    }
    
    public function set_ei_comment( $parser, $frame, $s ) {
        if ( $s === null ) {
            $this->ei_comment = null;
            return true;
        }
        $this->ei_comment = (string) $parser->recursiveTagParse( EImageStatic::href( nl2br ( trim ( $s ) ) ), $frame );
        return true;
    }

    public function add_ei_imgurl( $width, $url, $reset=false ) {
        if ( !is_numeric( $width ) ) return false;
        $width = (int) $width;
        // Remove all URLs if $reset is true.
        if ( $reset === true ) $this->ei_imgurl = array();
        // Remove a URL from the array if $url is null.
        if ( $url === null && array_key_exists( $width, $this->ei_imgurl ) ) {
            unset( $this->ei_imgurl[$width] );
            return true;
        }
        $url = (string) htmlspecialchars( $url );
        //$url = (string) filter_var( $url, FILTER_VALIDATE_URL );
        if ( $url !== '' ) {
            $this->ei_imgurl[$width] = $url;
            return true;
        }
        return false;
    }

    public function set_ei_imgurlfs( $s ) {
        if ( $s === null ) {
            $this->ei_imgurlfs = null;
            return true;
        }
        //$s = (string) filter_var( $s, FILTER_VALIDATE_URL );
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->ei_imgurlfs = $s;
            return true;
        }
        return false;
    }

    public function set_ei_imgurlpage( $s ) {
        if ( $s === null ) {
            $this->ei_imgurlpage = null;
            return true;
        }
        $s = (string) filter_var( $s, FILTER_VALIDATE_URL );
        if ( $s !== '' ) {
            $this->ei_imgurlpage = $s;
            return true;
        }
        return false;
    }

    public function set_ei_errormsg( $s ) {
        if ( $s === null ) {
            $this->ei_errormsg = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->ei_errormsg = $s;
            return true;
        }
        return false;
    }

    public function setWidth( $i ) {
        $i = (int) $i;
        if ( $i < 1 ) $this->width = null;
        else $this->width = $i;
        return true;
    }

    public function setHeight( $i ) {
        $i = (int) $i;
        if ( $i < 1 ) $this->height = null;
        else $this->height = $i;
        return true;
    }

    public function setLink( $s ) {
        // Sets $link even if user specifies an empty (or invalid, which will become empty) link. This is because if 
        // the user specifies a blank link, the image will link to anything (default is it links to $ei_imgurlpage).
        if ( $s === null ) {
            $this->link = null;
            return true;
        }
        $s = (string) filter_var( $s, FILTER_VALIDATE_URL );
        $this->link = $s;
        return true;
    }

    public function setAlt( $s ) {
        if ( $s === null ) {
            $this->alt = null;
            return true;
        }
        $s = (string) Sanitizer::stripAllTags( $s );
        if ( $s !== '' ) {
            $this->alt = $s;
            return true;
        }
        return false;
    }

    public function setTitle( $s ) {
        if ( $s === null ) {
            $this->title = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->title = $s;
            return true;
        }
        return false;
    }

    public function setCaption( $parser, $frame, $s ) {
        if ( $s === null ) {
            $this->caption = null;
            return true;
        }
        $this->caption = (string) $parser->recursiveTagParse( $s, $frame );
        return true;
    }

    public function setFormat( $s ) {
        if ( in_array( $s, array( 'thumb', 'frame', 'frameless' ) ) ) {
            $this->format = $s;
            return true;
        }
        return false;
    }

    public function setBorderTrue() {
        $this->border = true;
        return true;
    }

    public function setHAlign( $s ) {
        if ( in_array( $s, array( 'center', 'right', 'left', 'none' ) ) ) {
            $this->hAlign = $s;
            return true;
        }
        return false;
    }

    public function setInlineTrue() {
        $this->inline = true;
        return true;
    }

    public function setVAlign( $s ) {
        if (in_array($s, array('baseline', 'sub', 'super', 'top', 'text-top', 'middle', 'bottom', 'text-bottom'))) {
            $this->vAlign = $s;
            return true;
        }
        return false;
    }

    // Set annotation properties.
    public function setAAlign( $s ) {
        if ( $s === null ) {
            $this->aAlign = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aAlign = $s;
            return true;
        }
        return false;
    }

    public function setABg( $s ) {
        if ( $s === null ) {
            $this->aBg = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aBg = $s;
            return true;
        }
        return false;
    }

    public function setAFamily( $s ) {
        if ( $s === null ) {
            $this->aFamily = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aFamily = $s;
            return true;
        }
        return false;
    }
    
    public function setASize( $s ) {
        if ( $s === null ) {
            $this->aSize = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aSize = $s;
            return true;
        }
        return false;
    }

    public function setAWeight( $s ) {
        if ( $s === null ) {
            $this->aWeight = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aWeight = $s;
            return true;
        }
        return false;
    }
    
    public function setAStyle( $s ) {
        if ( $s === null ) {
            $this->aStyle = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aStyle = $s;
            return true;
        }
        return false;
    }

    public function setAShadow( $s ) {
        if ( $s === null ) {
            $this->aShadow = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aShadow = $s;
            return true;
        }
        return false;
    }
    
    public function setAColor( $s ) {
        if ( $s === null ) {
            $this->aColor = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aColor = $s;
            return true;
        }
        return false;
    }

    public function setAHeight( $s ) {
        if ( $s === null ) {
            $this->aHeight = null;
            return true;
        }
        $s = (string) htmlspecialchars( $s );
        if ( $s !== '' ) {
            $this->aHeight = $s;
            return true;
        }
        return false;
    }

    public function addAnnot( $parser, $frame, $s ) {
        array_push( $this->annot, $parser->recursiveTagParse( (string) $s, $frame ) );
        return true;
    }

    // Get properties
    public function get_ei_width() { return $this->ei_width; }
    public function get_ei_height() { return $this->ei_height; }
    public function get_ei_filename() { return $this->ei_filename; }
    public function get_ei_title() { return $this->ei_title; }
    public function get_ei_comment() { return $this->ei_comment; }
    public function get_ei_imgurl() { return $this->ei_imgurl; }
    public function get_ei_imgurlfs() { return $this->ei_imgurlfs; }
    public function get_ei_imgurlpage() { return $this->ei_imgurlpage; }
    public function get_ei_errormsg() { return $this->ei_errormsg; }
    public function getWidth() { return $this->width; }
    public function getHeight() { return $this->height; }
    public function getLink() { return $this->link; }
    public function getAlt() { return $this->alt; }
    public function getTitle() { return $this->title; }
    public function getCaption() { return $this->caption; }
    public function getFormat() { return $this->format; }
    public function getBorder() { return $this->border; }
    public function getHAlign() { return $this->hAlign; }
    public function getInline() { return $this->inline; }
    public function getVAlign() { return $this->vAlign; }
    public function getAAlign() { return $this->aAlign; }
    public function getABg() { return $this->aBg; }
    public function getAFamily() { return $this->aFamily; }
    public function getASize() { return $this->aSize; }
    public function getAWeight() { return $this->aWeight; }
    public function getAStyle() { return $this->aStyle; }
    public function getAShadow() { return $this->aShadow; }
    public function getAColor() { return $this->aColor; }
    public function getAHeight() { return $this->aHeight; }
    public function getAnnot() { return $this->annot; }

    /*
     * Replaces !!TITLE!!, !!COMMENT!!, and !!FNAME!! in $alt and $caption.
     *
     * @return Boolean: always true
     */
    public function replaceFromHost() {
        if ( $this->alt !== null ) {
            $this->alt = str_replace( '!!TITLE!!', $this->ei_title, $this->alt );
            $this->alt = str_replace( '!!COMMENT!!', $this->ei_comment, $this->alt );
            $this->alt = str_replace( '!!FNAME!!', $this->ei_filename, $this->alt );
        }
        if ( $this->caption !== null ) {
            $this->caption = str_replace( '!!TITLE!!', $this->ei_title, $this->caption );
            $this->caption = str_replace( '!!COMMENT!!', $this->ei_comment, $this->caption );
            $this->caption = str_replace( '!!FNAME!!', $this->ei_filename, $this->caption );
        }
        return true;
    }
    
    /*
     * Returns the best URL in $ei_imgurl[] to display based on $width. The idea is to chose the smallest image 
     * without having the user's browser expand/upscale the image. Ideally the browser will always downscale. The 
     * exception to this is if the only URL available requires expanding (such as a small image with no larger 
     * versions).
     *
     * @return String: URL of image to display
     */
    public function getBestImgUrl() {
        krsort( $this->ei_imgurl );
        $url = reset( $this->ei_imgurl ); // Initially sets the biggest image.
        foreach ( $this->ei_imgurl as $k=>$v ) {
            // Choose the smallest image. If there's no choice, the initially set url (done above) is used.
            if ( $k < $this->width ) break;
            $url = $v;
        }
        return $url;
    }

    /*
     * Sets $link with some funny conditions. If $link is null, this means the user did not specify a link, which by 
     * default means the link will be $ei_imgurlpage. If $link is not null but is an empty string, then the user is 
     * telling us they don't want the image to link to anything (no <a href='' />) in which case we will set $link to 
     * null to tell the extension we don't want a link. If $link is set to a valid URL (will be an empty string if 
     * invalid) then we use that. I'm sorry.
     *
     * @return Boolean: always true
     */
    public function linkJuggle() {
        if ( $this->link === null ) {
            // $link is not set by user, setting default.
            $this->link = $this->ei_imgurlpage;
        } elseif ( $this->link === '' ) {
            // $link is set but user provided empty string. This means they don't want a link. Unsetting $link.
            $this->link = null;
        }
        return true;
    }

    /*
     * Applies restrictions to $width, sets defaults to it, or updates it if $height is set.
     *
     * @return Boolean: always true
     */
    public function amendWidth() {
        if ( $this->height !== null ) {
            // If user sets width AND height, fit image within dimensions. If just height, determine the width.
            $width = floor( $this->height / $this->ei_height * $this->ei_width );
            if ( $this->width === null || $this->width > $width ) $this->width = $width;
        }
        if ( $this->width === null) {
            if ( in_array( $this->format, array( 'frameless', 'thumb' ) ) ) {
                // User did not specify a width or height. Thumbnails and "frameless" defalt to user setting.
                global $wgThumbLimits;
                $this->width = (int) $wgThumbLimits[User::getDefaultOption( 'thumbsize' )];
            } else {
                // Default to full size.
                $this->width = $this->ei_width;
            }
        }
        if ( $this->format == 'thumb' && $this->width > $this->ei_width ) {
            // Thumbs do not enlarge.
            $this->width = $this->ei_width;
        }
        return true;
    }

    /*
     * Update $alt and $title similar to how MediaWiki does it with local images.
     *
     * @return Boolean: always true
     */
    public function amendAltTitle() {
        // Conditionally set image alt and title text. From ./includes/parser/Parser.php
        if ( in_array( $this->format, array( 'frame', 'thumb' ) ) ) { # Framed image
            if ( $this->caption === null && $this->alt === null && $this->ei_filename !== null ) {
                # No caption or alt text, add the filename as the alt text so
                # that screen readers at least get some description of the image
                $this->alt = (string) Sanitizer::stripAllTags( $this->ei_filename );
            }
            # Do not set $this->title because tooltips don't make sense
            # for framed images
        } else { # Inline image
            if ( $this->alt === null ) {
                # No alt text, use the "caption" for the alt text
                if ( $this->caption !== null ) {
                    $this->alt = (string) Sanitizer::stripAllTags( $this->caption );
                } elseif ( $this->ei_filename !== null ) {
                    # No caption, fall back to using the filename for the
                    # alt text
                    $this->alt = (string) Sanitizer::stripAllTags( $this->ei_filename );
                }
            }
            # Use the "caption" for the tooltip text
            $this->title = Sanitizer::stripAllTags( $this->caption );
        }
        // If $this->ei_errormsg is set, always show that as title.
        //$this->title = Sanitizer::stripAllTags( $this->ei_errormsg );
        return true;
    }
}
