<?php
/**
 * Calculate several statistics for WikiPathways.
 *
 * Run using PHP cli:
 *
 * php statistics/Generator.php [task]
 *
 * Tasks will be loaded from php files in the tasks directory.
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

use Maintenance;

/* Load MW classes */
$maintenance = __DIR__ . "/../../../mediawiki/maintenance/Maintenance.php";
if (!file_exists($maintenance) ) {
    $IP = getenv("MW_INSTALL_DIR");
    if ($IP ) {
        $maintenance = "$IP/maintenance/Maintenance.php";
    } else {
        echo "Please set the environment variable MW_INSTALL_DIR to the mediawiki installation.\n";
        exit(2);
    }
}
require_once $maintenance;

class Generator extends Maintenance
{
    private $basedir = __DIR__ . "/tasks";
    private $startY = 2008;
    private $startM = 1;
    private $startD = 1;
    private $allTasks = [];
    private static $instance;

    public function __construct() 
    {
        parent::__construct();
        $this->description = "Calculate several statistics for WikiPathways.";
        self::$instance = $this;
    }

    public function execute() 
    {
        $tsStart = date(
            'YmdHis', mktime(0, 0, 0, $this->startM, $this->startD, $this->startY)
        );
        $tsEnd = date('YmdHis', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $times = Statistics::getTimeStampPerMonth($tsStart, $tsEnd);

        foreach ( scandir($this->basedir) as $file ) {
            $fullPath = $this->basedir . '/' . $file;
            if (preg_match('/\.php$/', $file) && is_readable($fullPath) ) {
                // Load the file, it will register itself to the available tasks.
                include_once $fullPath;
            }
        }

        $tasks = [];
        if (count($this->mArgs) == 0 || in_array('all', $this->mArgs) ) {
            $tasks = array_keys($this->allTasks);
        } else {
            $tasks = $this->mArgs;
        }

        foreach ( $tasks as $task ) {
            if (!in_array($task, array_keys($this->allTasks)) ) {
                $this->output("ERROR: Unknown task: $task\n");
                $this->output("Please leave blank to run all tasks or choose from the following:");
                $this->output("\n\t" . implode("\n\t", array_keys($this->allTasks)) . "\n");
                continue;
            }
            $file = $this->basedir . '/' . $task . '.txt';
            if (!is_writable($file) ) {
                if (file_exists($file) ) {
                    $this->error("Cannot write to $file", true);
                }
                if (!is_writable(dirname($file)) ) {
                    $this->error("Cannot create $file", true);
                }
            }
            if (!is_callable($this->allTasks[$task]) ) {
                $this->error(
                    "$task does not have a callable task", true
                );
            }
            $this->output("Running task $task:\n");
            call_user_func($this->allTasks[$task], $file, $times);
        }
    }

    public function instance() 
    {
        if (!self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function registerTask( $name, $functionName ) 
    {
        $inst = self::instance();
        $inst->allTasks[$name] = $functionName;
    }
}

$maintClass = 'Wikipathways\Statistics\Generator';
require_once RUN_MAINTENANCE_IF_MAIN;
