<?php
/**
 * Queries information about registered users.
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
namespace WikiPathways\Statistics\Task;

class User
{
    static function getSnapshot($timestamp) 
    {
        $snapshot = array();

        $q = <<<QUERY
SELECT user_id, user_name, user_real_name FROM user
WHERE user_registration <= $timestamp
QUERY;

        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->query($q);
        while($row = $dbr->fetchObject($res)) {
            $u = new User($row->user_id, $row->user_name, $row->user_real_name);
            $snapshot[] = $u;
        }
        $dbr->freeResult($res);

        return $snapshot;
    }

    private $id;
    private $name;
    private $realName;

    function __construct($id, $name, $realName) 
    {
        $this->id = $id;
        $this->name = $name;
        $this->realName = $realName;
    }

    function getId() 
    {
        return $this->id; 
    }
    function getName() 
    {
        return $this->name; 
    }
    function getRealName() 
    {
        return $this->realName; 
    }

    function getPageEdits($tsTo = '', $tsFrom = '') 
    {
        $pageEdits = array();

        $qto = '';
        $qfrom = '';
        if($tsTo) { $qto = "AND r.rev_timestamp <= $tsTo "; 
        }
        if($tsFrom) { $qfrom = "AND r.rev_timestamp > $tsFrom "; 
        }
        $ns_pathway = NS_PATHWAY;
        $q = <<<QUERY
SELECT r.rev_page FROM revision AS r JOIN page AS p
WHERE r.rev_user = $this->id AND p.page_namespace = $ns_pathway
AND p.page_id = r.rev_page
$qfrom $qto
QUERY;
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->query($q);
        while($row = $dbr->fetchRow($res)) {
            $pageEdits[] = $row[0];
        }
        $dbr->freeResult($res);

        return $pageEdits;
    }
}

