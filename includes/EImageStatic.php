<?php
/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 */

class EImageStatic {
    /*
     * Inspired by preg_match and Python's str.startswith(). Returns true if $haystack starts with $key. Optionally 
     * chops off $key from $haystack and sets $value to the remainder (value in a key=value pair).
     *
     * @param String $haystack: Haystack to search through.
     * @param String $key: Needle at the beginning of $haystack.
     * @param String $value: If set, will be overwritten by the haystack with the leading needle removed.
     * @return Boolean: true if $haystack starts with $needle, false otherwise.
     */
    public static function startsWith( $haystack, $key, &$value=null ) {
        if ( !strncmp( $haystack, $key, strlen( $key ) ) ) {
            // $haystack starts with $key.
            $value = (string) substr( $haystack, strlen( $key ) );
            return true;
        }
        return false;
    }

    /*
     * Returns null instead of empty strings or integers with a value of 0. Otherwise return the input unchanged.
     */
    public static function nullIfEmpty( $s ) {
        if ( $s == '' || $s == 0 ) return null;
        return $s;
    }

    /*
     * Queries an image host for image metadata.
     *
     * @param String $url: The URL to query.
     * @param Array $pvars: POST variables to send with the query.
     * @param Array $json: If set, will be overwritten by the JSON response encoded in an associative array.
     * @return Integer: Returns the HTTP status of the query. Usually 200 indicates success.
     */
    public static function curl( $url, $pvars=array(), &$json=null, $headers=array() ) {
        $c = curl_init();
        curl_setopt( $c, CURLOPT_TIMEOUT, 5 );
        curl_setopt( $c, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $c, CURLOPT_URL, $url );
        if ( !empty( $headers ) ) curl_setopt( $c, CURLOPT_HTTPHEADER, $headers );
        if ( !empty( $pvars ) ) {
            curl_setopt( $c, CURLOPT_POST, 1 );
            curl_setopt( $c, CURLOPT_POSTFIELDS, $pvars );
        }
        $json = (array) json_decode( curl_exec( $c ), true );
        $status = (int) curl_getinfo( $c, CURLINFO_HTTP_CODE );
        curl_close( $c );
        return $status;
    }

    /*
     * Converts HTML links to wiki-text links. Mainly used for Flickr titles and comments/descriptions. A lot of 
     * Flickr images have links in those fields, which come to EImage as HTML. MediaWiki doesn't whitelist HTML links 
     * by default so they must be converted to wiki-text before sending them to the parser.
     *
     * @param String $text: The string to be converted.
     * @return String: The converted string.
     */
    public static function href( $text ) {
        require_once 'JSLikeHTMLElement.php';
        while ( true ) {
            // Replace links one by one. replaceChild() breaks loops so I have to do everything ever iteration.
            $doc = new DOMDocument();
            $doc->registerNodeClass( 'DOMElement', 'JSLikeHTMLElement' );
            $doc->loadHTML( '<body>' . mb_convert_encoding( $text, 'html-entities', 'utf-8' ) . '</body>' );
            $links = $doc->getElementsByTagName( 'a' );
            if ( $links->length < 1 ) break;
            $link = $links->item( 0 );
            $href = (string) $link->getAttribute('href');
            $label = (string) $link->nodeValue;
            $replacement = $label !== '' ? "[{$href} {$label}]" : $href;
            $link->parentNode->replaceChild( $doc->createTextNode( $replacement ), $link );
            $text = $doc->getElementsByTagName( 'body' )->item( 0 )->innerHTML;
        }
        return $text;
    }

    /*
     * Called when user wants to purge a wiki article. Does this by setting the last update time of all images from 
     * the requested article to a time older than the $wgEImageStaleMinutes threshold. This is done before the page 
     * loads, so when the page loads after MediaWiki purges the page, the page will query the host API for new data.
     *
     * @param Article $article
     * @return Boolean: Always true.
     */
    public static function purge( $article ) {
        global $wgDBprefix, $wgEImageStaleMinutes, $wgEImageTableName;
        $title = base64_encode( $article->getTitle()->getText() ); // Similar to {{PAGENAME}}.
        $minus = (int) ($wgEImageStaleMinutes * 61);
        $dbw = wfGetDB( DB_MASTER );
        $dbw->query(
            "UPDATE `{$wgDBprefix}{$wgEImageTableName}`" .
            " SET ei_time_lu = ei_time_lu - $minus" .
            " WHERE ei_articles LIKE '%{$title}%' /*\$wgDBTableOptions*/"
        );
        return true;
    }

    /*
     * Called when the administrator runs MediaWiki's update.php script. Creates the required database table which
     * EImage needs to cache metadata.
     *
     * @param DatabaseUpdater $updater
     * @return Boolean: Always true.
     */
    public static function createTables( DatabaseUpdater $updater ) {
        global $wgEImageTableName;
        $base = dirname( __FILE__ ) . '/../schema';
        $updater->addExtensionTable( $wgEImageTableName, "{$base}/create_table.sql" );
        return true;
    }

    /*
     * In order to avoid having the MediaWiki parser double-parse the output of EImage, we encode the output during
     * processing and then have the parser decode the output at the end.
     *
     * @param Parser $parser
     * @param String $text
     * @return Boolean: Always true.
     */
    public static function decode( &$parser, &$text ) {
        $count = 0;
        do {
            $text = preg_replace( 
                '/ENCODED_EIMAGE_CONTENT ([0-9a-zA-Z\/+]+=*)* END_ENCODED_EIMAGE_CONTENT/esm',
                'base64_decode("$1")',
                $text,
                -1,
                $count
            );
        } while ( $count );
    	return true;
    }
}
