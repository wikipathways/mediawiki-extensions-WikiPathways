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

class UniquePerSpecies
{
    static function run($file, $times) 
    {
        $last = array_pop($times);

        //Create histogram of in how many species each pathway title occurs
        $speciesPerTitle = array();
        $pathways = StatPathway::getSnapshot($last);
        foreach($pathways as $p) {
            $wp = new Pathway($p->getPwId());
            $name = $wp->getName();
            $species = $wp->getSpecies();

            if(array_key_exists($name, $speciesPerTitle)) {
                $speciesPerTitle[$name][$species] = 1;
            } else {
                $speciesPerTitle[$name] = array($species => 1);
            }
        }

        $countsPerTitle = array();
        foreach(array_keys($speciesPerTitle) as $name) {
            $countsPerTitle[$name] = count(array_keys($speciesPerTitle[$name]));
        }

        $hist = array();
        for($i = min($countsPerTitle); $i <= max($countsPerTitle); $i++) { $hist[$i] = 0; 
        }

        foreach(array_keys($countsPerTitle) as $name) {
            $number = $countsPerTitle[$name];
            $hist[$number] = $hist[$number] + 1;
        }

        //Export historgram
        $fout = fopen($file, 'w');
        fwrite($fout, "string\tnumber\n");
        fwrite($fout, "Number of species\tNumber of pathway titles\n");

        foreach(array_keys($hist) as $number) {
            $row = array(
             $number, $hist[$number]
            );
            fwrite($fout, implode("\t", $row) . "\n");
        }

        fclose($fout);

        //Export individual titles and species
        $fout = fopen($file . '.titles', 'w');
        fwrite($fout, "string\tstring\tnumber\n");
        fwrite($fout, "Pathway title\tPresent in species\tNumber of species\n");

        foreach(array_keys($speciesPerTitle) as $title) {
            $species = array_keys($speciesPerTitle[$title]);
            sort($species);

            $row = array(
             $title, implode(", ", $species), $countsPerTitle[$title]
            );
            fwrite($fout, implode("\t", $row) . "\n");
        }

        fclose($fout);
    }
}

Generator::registerTask(
    'UniquePerSpecies', __NAMESPACE__ . '\UniquePerSpecies::run'
);
