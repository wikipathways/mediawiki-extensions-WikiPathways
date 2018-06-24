<?php
/**
 * Queries information about curation tags.
 *
 * Copyright (C) 2018  J. David Gladstone Institutes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\Statistics;

class Tag
{
    static function getSnapshot($timestamp, $tagTypes) 
    {
        $snapshot = array();

        $q = <<<QUERY
SELECT tag_name, page_id FROM tag_history
WHERE tag_name LIKE 'Curation:%'
AND action = 'create' AND time <= $timestamp;
QUERY;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->query($q);
        while($row = $dbr->fetchObject($res)) {
            $snapshot[] =  new Tag($row->tag_name, $row->page_id);
        }
        $dbr->freeResult($res);

        array_unique($snapshot); //remove duplicates

        //Only include collection tags
        $removeTags = array();
        foreach($snapshot as $tag) { if(!in_array($tag->getType(), $tagTypes)) {
                $removeTags[] = $tag;
        } 
        }
        $snapshot = array_diff($snapshot, $removeTags);

        $remove = array();
        foreach($snapshot as $tag) {
            //For each curation tag, find:
            //- the latest create before date
            //- the latest delete before date
            //Compare, if !delete or create > delete then exists
            $q_remove = <<<QUERY
SELECT time FROM tag_history
WHERE tag_name = '$tag->type' AND page_id = $tag->pageId
AND action = 'remove' AND time <= $timestamp
ORDER BY time DESC
QUERY;
            $res = $dbr->query($q_remove);
            $row = $dbr->fetchRow($res);
            $latest_remove = $row[0];
            $dbr->freeResult($res);

            if(!$latest_remove) { continue; 
            }

            $q_create = <<<QUERY
SELECT time FROM tag_history
WHERE tag_name = '$tag->type' AND page_id = $tag->pageId
AND action = 'create' AND time <= $timestamp
ORDER BY time DESC
QUERY;
            $res = $dbr->query($q_create);
            $row = $dbr->fetchRow($res);
            $latest_create = $row[0];
            $dbr->freeResult($res);

            if($latest_remove > $latest_create) { $remove[] = $tag; 
            }
        }

        $snapshot = array_diff($snapshot, $remove);
        return $snapshot;
    }

    private $type;
    private $pageId;

    function __construct($type, $pageId) 
    {
        $this->type = $type;
        $this->pageId = $pageId;
    }

    function __toString() 
    {
        return $this->type . $this->pageId;
    }

    function getPageId() 
    {
        return $this->pageId; 
    }
    function getType() 
    {
        return $this->type; 
    }
}

