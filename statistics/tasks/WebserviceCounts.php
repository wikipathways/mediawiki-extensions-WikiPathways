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

use WikiPathways\Statistics\Generator;

class WebserviceCounts
{
    static function run($file, $times) 
    {
        $ownIps = array(
         '/137\.120\.14\.[0-9]{1,3}/',
         '/137\.120\.89\.38/',
         '/137\.120\.89\.24/',
         '/137\.120\.17\.25/',
         '/137\.120\.17\.35/',
         '/137\.120\.17\.33/',
         '/169\.230\.76\.87/'
        );

        $dates = array();
        $own = array();
        $ext = array();

        $tsPrev = array_shift($times);
        foreach($times as $tsCurr) {
            $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));
            wfDebugLog(__NAMESPACE__, $date);

            $ipCounts = StatWebservice::getCountsByIp($tsPrev, $tsCurr);
            $ownCount = 0;
            $extCount = 0;

            foreach(array_keys($ipCounts) as $ip) {
                $isOwn = false;
                foreach($ownIps as $r) {
                    if(preg_match($r, $ip)) {
                        $isOwn = true;
                        break;
                    }
                }
                if($isOwn) { $ownCount += $ipCounts[$ip]; 
                }
                else { $extCount += $ipCounts[$ip]; 
                }
            }

            $own[$date] = $ownCount;
            $ext[$date] = $extCount;
            $dates[] = $date;
            $tsPrev = $tsCurr;
        }

        $fout = fopen($file, 'w');
        fwrite($fout, "date\tnumber\tnumber\n");
        fwrite($fout, "Time\tExternal\tInternal\n");

        foreach($dates as $date) {
            $row = array(
             $date, $ext[$date], $own[$date]
            );
            fwrite($fout, implode("\t", $row) . "\n");
        }

        fclose($fout);
    }
}

Generator::registerTask('WebserviceCounts', __NAMESPACE__ . '\WebserviceCounts::run');
