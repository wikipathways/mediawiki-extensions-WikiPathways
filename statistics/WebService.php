<?php

/**
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

class StatWebservice
{
    static function getCountsByIp($tsFrom, $tsTo) 
    {
        $q = <<<QUERY
SELECT ip, count(ip) FROM webservice_log
WHERE request_timestamp >= $tsFrom AND request_timestamp < $tsTo
GROUP BY ip ORDER BY count(ip) DESC
QUERY;

        $counts = array();

        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->query($q);
        while($row = $dbr->fetchRow($res)) {
            $counts[$row[0]] = $row[1];
        }
        $dbr->freeResult($res);

        return $counts;
    }

    static function getCounts($tsFrom, $tsTo) 
    {
        $snapshot = array();

        $q = <<<QUERY
SELECT count(ip) FROM webservice_log
WHERE request_timestamp >= $tsFrom AND request_timestamp < $tsTo
QUERY;

        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->query($q);
        $row = $dbr->fetchRow($res);
        $count = $row[0];
        $dbr->freeResult($res);

        return $count;
    }
}

