<?php

/**
 * Copyright (c) 2013, Robpol86
 * This software is made available under the terms of the MIT License that can
 * be found in the LICENSE.txt file.
 *
 * Requirements:
 *      PHP 5.2.3 (with php-mbstring)
 *      MediaWiki 1.18.2
 *      Run update.php
 *
 */
$wgExtensionCredits['parserhook'][] = array(
    'path'              => __FILE__,
    'name'              => 'EImage',
    'descriptionmsg'    => 'eimage-desc',
    'version'           => '2.0.0',
    'author'            => '[http://www.robpol86.com/index.php/User:Robpol86 Robpol86]',
    'url'               => 'https://www.mediawiki.org/wiki/Extension:EImage'
);

/**
 * Options:
 *
 * $wgEImageStaleMinutes
 *      - Refresh cached metadata if the stored entry is this many minutes old.
 *      - Default is 60.
 */

$wgEImageStaleMinutes = 60;
$wgEImageTableName = 'eimage_metadata_cache';
$wgEImageEmptyPng = $wgScriptPath . str_replace( $IP, '', dirname( __FILE__ ) . '/empty.png' );

// Setup MediaWiki parser hooks.
$wgEImageIncludes = dirname( __FILE__ ) . '/includes';
$wgAutoloadClasses['EImageData'] = $wgEImageIncludes . '/EImageData.php';
$wgAutoloadClasses['EImageQuery'] = $wgEImageIncludes . '/EImageQuery.php';
$wgAutoloadClasses['EImageQueryFlickr'] = $wgEImageIncludes . '/EImageQueryFlickr.php';
$wgAutoloadClasses['EImageQueryImgur'] = $wgEImageIncludes . '/EImageQueryImgur.php';
$wgAutoloadClasses['EImageQueryRaw'] = $wgEImageIncludes . '/EImageQueryRaw.php';
$wgAutoloadClasses['EImageStatic'] = $wgEImageIncludes . '/EImageStatic.php';
$wgAutoloadClasses['EImageStaticAnnot'] = $wgEImageIncludes . '/EImageStaticAnnot.php';
$wgAutoloadClasses['EImageStaticHtml'] = $wgEImageIncludes . '/EImageStaticHtml.php';
$wgAutoloadClasses['EImageStaticMain'] = $wgEImageIncludes . '/EImageStaticMain.php';
$wgExtensionMessagesFiles['EImage'] = dirname( __FILE__ ) . '/EImage.i18n.php';
$wgExtensionMessagesFiles['EImageMagic'] = dirname( __FILE__ ) . '/EImage.i18n.magic.php';
$wgHooks['ParserFirstCallInit'][] = 'wfRegisterEImage';
function wfRegisterEImage( &$parser ) {
    // Function hook for annotation.
    $parser->setFunctionHook( 'eimagea', 'EImageStaticAnnot::annotation', SFH_OBJECT_ARGS );
    // Function hook for image.
    $parser->setFunctionHook( 'eimage', 'EImageStaticMain::readInput', SFH_OBJECT_ARGS );
    return true;
}

// Add hook to update.php for creating tables.
$wgHooks['LoadExtensionSchemaUpdates'][] = 'EImageStatic::createTables';

// Restore EImage output to HTML.
$wgHooks['ParserBeforeTidy'][] = 'EImageStatic::decode';

// Add hook to article purge.
$wgHooks['ArticlePurge'][] = 'EImageStatic::purge';

