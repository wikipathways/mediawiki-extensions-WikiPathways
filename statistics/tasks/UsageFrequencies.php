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

class UsageFrequencies
{
    /**
     * Frequency of number of views and number of edits.
     */
    static function run($file, $times) 
    {
        $tsCurr = array_pop($times);

        $viewCounts = array();
        $editCounts = array();

        $i = 0;
        $pathways = StatPathway::getSnapshot($tsCurr);
        $total = count($pathways);
        foreach($pathways as $p) {
            if(($i % 100) == 0) { wfDebugLog(__NAMESPACE__, "Processing $i out of $total"); 
            }
            $i++;

            array_push($viewCounts, $p->getNrViews());
            array_push($editCounts, $p->getNrRevisions());
        }

        $fout = fopen($file . ".edits", 'w');
        fwrite($fout, "string\tnumber\n");
        fwrite($fout, "Pathway rank (by number of edits)\tNumber of edits\n");

        WikiPathwaysStatistics::writeFrequencies($fout, $viewCounts);

        fclose($fout);

        $fout = fopen($file . ".views", 'w');
        fwrite($fout, "string\tnumber\n");
        fwrite($fout, "Pathway rank (by number of views)\tNumber of views\n");

        WikiPathwaysStatistics::writeFrequencies($fout, $viewCounts);

        fclose($fout);
    }
}

Generator::registerTask('UsageFrequencies', __NAMESPACE__ . '\UsageFrequencies::run');
