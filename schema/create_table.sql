-- Copyright (c) 2012, Robpol86
-- This software is made available under the terms of the MIT License that can
-- be found in the LICENSE.txt file.
-- 
-- Referenced https://svn.wikia-code.com/vendor/mediawiki/REL1_19/extensions/SelectionSifter/schema/ratings.sql
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options

CREATE TABLE IF NOT EXISTS /*_*/eimage_metadata_cache (
    -- time of original/first insert
    ei_time_og      int NOT NULL,

    -- time of last update
    ei_time_lu      int NOT NULL,

    -- image ID
    ei_image        varchar(255) NOT NULL,

    -- base64 encoded titles of articles (space delimited) that use this image (used for ?action=purge)
    ei_articles     text,
    
    -- image host
    ei_host         varchar(255) NOT NULL,

    -- image native width
    ei_width        int NOT NULL,
    
    -- image native height
    ei_height       int NOT NULL,
    
    -- image filename
    ei_filename     varchar(255),
    
    -- image metadata title/subject
    ei_title        text,
    
    -- image metadata comment/description
    ei_comment      text,
    
    -- serialized PHP associative array of widths and image URLs
    ei_imgurl       text NOT NULL,
    
    -- URL to full size image
    ei_imgurlfs     varchar(255),
    
    -- URL to image host's web page for this image
    ei_imgurlpage   varchar(255),

    -- error message
    ei_errormsg     text,
    
    PRIMARY KEY (ei_image, ei_host)
) /*$wgDBTableOptions*/;

