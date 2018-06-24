<?php
/**
 * Queries information about pathway entries.
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

use WikiPathways\Pathway as WikiPathway;
use Exception;

class Pathway
{
    public static function getSnapshot( $timestamp ) 
    {
        $snapshot = [];

        $ns_pathway = NS_PATHWAY;
        $dbr = wfGetDB(DB_REPLICA);

        $res = $dbr->select(
            [ 'page', 'revision' ], ['page_title', 'page_id', 'MAX(rev_id) as rev'],
            [
            'rev_timestamp <= ' . $timestamp, 'page_is_redirect' => 0,
            'page_namespace' => NS_PATHWAY
            ], __METHOD__, [ 'DISTINCT' ], [ 'revision' => [ 'JOIN', 'rev_page=page_id' ] ]
        );
        while ( $row = $dbr->fetchObject($res) ) {
            $pathway = new WikiPathway($row->page_title, $row->page_id);
            if (!$pathway->isDeleted() && !$pathway->getTitleObject()->isRedirect() ) {
                $snapshot[] = $pathway;
            }
        }

        return $snapshot;
    }

    public static function getNrRevisions() 
    {
        $dbr = wfGetDB(DB_REPLICA);
        $q = <<<QUERY
SELECT COUNT(rev_id) FROM revision WHERE rev_page = $this->pageId
QUERY;
        $dbr = wfGetDB(DB_REPLICA);
        $res = $dbr->query($q);
        $row = $dbr->fetchRow($res);
        $count = $row[0];
        $dbr->freeResult($res);
        return $count;
    }

    public static function getNrViews() 
    {
        $q = <<<QUERY
SELECT page_counter FROM page WHERE page_id = $this->pageId
QUERY;
        $dbr = wfGetDB(DB_REPLICA);
        $res = $dbr->query($q);
        $row = $dbr->fetchRow($res);
        $count = $row[0];
        $dbr->freeResult($res);
        return $count;
    }
}
