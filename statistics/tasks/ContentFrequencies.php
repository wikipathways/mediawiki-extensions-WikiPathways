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

class ContentFrequencies
{
    /**
     * Frequencies of several pathway statistics:
     * - xrefs
     * - literature references
     * - linked lines (interactions)
     */
    static function run($file, $times) 
    {
         $tsCurr = array_pop($times);

         $xrefCounts = array();
         $litCounts = array();
         $intCounts = array();

         $i = 0;
         $pathways = StatPathway::getSnapshot($tsCurr);
         $total = count($pathways);
        foreach($pathways as $p) {
            if(($i % 100) == 0) { wfDebugLog(__NAMESPACE__, "Processing $i out of $total"); 
            }
            $i++;

            $wp = new Pathway($p->getPwId());
            if(!$wp->isReadable()) { continue; 
            }

            $wp->setActiveRevision($p->getRevision());
            $data = new PathwayData($wp);

            $xc = count($data->getUniqueXrefs());
            $lc = count($data->getPublicationXRefs());
            $ic = count($data->getInteractions());
            array_push($xrefCounts, $xc);
            array_push($litCounts, $lc);
            array_push($intCounts, $ic);
        }

         $fout = fopen("$file.xrefs", 'w');
         fwrite($fout, "string\tnumber\n");
         fwrite($fout, "Pathway rank (by number of xrefs)\tNumber of xrefs\n");
         WikiPathwaysStatistics::writeFrequencies($fout, $xrefCounts);
         fclose($fout);

         $fout = fopen("$file.lit", 'w');
         fwrite($fout, "string\tnumber\n");
         fwrite($fout, "Pathway rank (by number of literature references)\tNumber of literature references\n");
         WikiPathwaysStatistics::writeFrequencies($fout, $litCounts);
         fclose($fout);

         $fout = fopen("$file.int", 'w');
         fwrite($fout, "string\tnumber\n");
         fwrite($fout, "Pathway rank (by number of connected lines)\tNumber of connected lines\n");
         WikiPathwaysStatistics::writeFrequencies($fout, $intCounts);
         fclose($fout);
    }
}

Generator::registerTask('contentFrequencies', __NAMESPACE__ . '\ContentFrequencies::run');
