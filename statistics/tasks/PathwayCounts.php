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

class PathwayCounts
{
    public static function run($file, $times) 
    {
        $exclude = WikiPathwaysStatistics::getExcludeByTag();

        $allSpecies = array();
        $allCounts = array();

        foreach($times as $tsCurr) {
            $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));
            wfDebugLog(__NAMESPACE__, $date . ":\t", "");
            $snapshot = StatPathway::getSnapshot($tsCurr);
            wfDebugLog(__NAMESPACE__, count($snapshot));

            $total = 0;
            $counts = array();
            foreach($snapshot as $p) {
                if(in_array($p->getPageId(), $exclude)) { continue; 
                }
                $s = $p->getSpecies();
                $allSpecies[$s] = 1;
                if(array_key_exists($s, $counts)) {
                    $counts[$s] = $counts[$s] + 1;
                } else {
                    $counts[$s] = 1;
                }
                $total += 1;
            }
            $counts['All species'] = $total;
            $allCounts[$date] = $counts;
        }

        unset($allSpecies['undefined']);
        $allSpecies = array_keys($allSpecies);
        sort($allSpecies);
        array_unshift($allSpecies, "All species");

        $fout = fopen($file, 'w');
        fwrite(
            $fout, "date\t" .
            implode("\t", array_fill(0, count($allSpecies), "number")) . "\n"
        );
        fwrite(
            $fout, "Time\t" .
            implode("\t", $allSpecies) . "\n"
        );

        foreach(array_keys($allCounts) as $date) {
            $counts = $allCounts[$date];
            $values = array();
            foreach($allSpecies as $s) {
                $v = 0;
                if(array_key_exists($s, $counts)) { $v = $counts[$s]; 
                }
                $values[] = $v;
            }
            fwrite($fout, $date . "\t" . implode("\t", $values) . "\n");
        }

        fclose($fout);
    }
}

Generator::registerTask('PathwayCounts', __NAMESPACE__ . '\PathwayCounts::run');
