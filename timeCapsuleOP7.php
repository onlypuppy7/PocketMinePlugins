<?php
/*
__PocketMine Plugin__
name=TimeCapsuleOP7 (PHP8)
description=The numerated changes-only backup assistant for PocketMine-MP
version=0.5
author=Falk/onlypuppy7
class=TimeCapsule
apiversion=12.1
*/
class TimeCapsule implements Plugin
{
    private $api, $path;
    public function __construct(ServerAPI $api, $server = false)
    {
        $this->api = $api;
    }

    public function init()
    {
        if (file_exists(FILE_PATH . "backups")) {
            console("[TimeCapsule] Backup manager started");
            $this->config = new Config(FILE_PATH . "backups/data.yml", CONFIG_YAML, array(0, 0, "off"));
        } else {
            if (mkdir(FILE_PATH . "backups", 0700) == true) {
                console("[TimeCapsule] Configured Successfully");
                console("Check the FAQ on forums.pocketmine.net for help.");
                $this->config = new Config(FILE_PATH . "backups/data.yml", CONFIG_YAML, array(0, 0, "off"));
            } else {
                console("[TimeCapsule] Failed to configure, check permissions");
            }
        }
        $data = $this->api->plugin->readYAML(FILE_PATH . "backups/data.yml");
        $this->api->plugin->writeYAML(FILE_PATH . "backups/data.yml", $data);
        if (is_numeric($data[2])) {
            $this->api->schedule(($data[2]) * 60 * 60 * 20, array($this, "backup"), array(), true); //change to set autosave delay in ticks (hours x 60 (minutes) x 60 (seconds) x 20)
        }
        $this->api->console->register("backup", "Create a new backup", array($this, "backup"));
        $this->api->console->register("restore", "Restore the server to a previous backup", array($this, "restore"));
        $this->api->console->register("allbackups", "See a list of all your backups (optionally add number to display specific number of entries)", array($this, "allbackups"));
        $this->api->console->register("autobackupset", "Set autosave interval (in hours) or disable", array($this, "autobackupset"));
    }

    public function __destruct()
    {
    }
    public function recurse_copy($src, $dst, $past, $time)
    {
        $dir = opendir($src);
        if (!file_exists($dst)) {
            mkdir($dst, 0700, true);
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file, $past . '/' . $file, $time);

                } else {
                    if (filemtime($src . '/' . $file) > $time || !is_file($past . '/' . $file)) {

                        if (copy($src . '/' . $file, $dst . '/' . $file) == false) {
                            console($file);
                        }
                    } else {
                        link($past . '/' . $file, $dst . '/' . $file);

                    }
                }
            }
        }
        closedir($dir);
    }
    public function restore_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->restore_copy($src . '/' . $file, $dst . '/' . $file);

                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function autobackupset($cmd, $params, $issuer, $alias)
    {
        $data = $this->api->plugin->readYAML(FILE_PATH . "backups/data.yml");
        if (!empty($params) && (is_numeric($params[0]) || strtolower($params[0]) === 'off')) {
            $data[2] = $params[0];
            $this->api->plugin->writeYAML(FILE_PATH . "backups/data.yml", $data);
            console("Set autosave to every: " . $params[0] . " hours");
            console("RESTART for changes to take effect!");
        } else {
            console("Currently set to: " . $data[2] . " hours");
            console("Usage: autosave [off/interval in hours]");
        }

    }
    public function backup()
    {
        $data = $this->api->plugin->readYAML(FILE_PATH . "backups/data.yml");
        $id = $data[0] + 1;
        if (!is_dir(FILE_PATH . "backups/" . $data[0]) && $data[0] != 0) {
            console("[TimeCapsule] Previous backup not found, searching for new backup");
            for ($i = $data[0]; $i >= 0; $i--) {
                if ($i == 0) {
                    console("[TimeCapsule] No backups can be read, starting fresh.");
                    $id = 1;
                    $data[0] = 0;
                    $data[1] = 0;
                } else {
                    if (!is_dir(FILE_PATH . "backups/" . $i)) {
                        console("[TimeCapsule] Found new backup directory at " . $i);
                        $id = $i + 1;
                        $data[0] = $i;
                        $data[1] = filemtime(FILE_PATH . "backups/" . $i . "/.");

                    }
                }
            }
        }
        console("[TimeCapsule] Backup started with ID:" . $id);
        console("[TimeCapsule] Making backup directories...");
        mkdir(FILE_PATH . "backups/" . $id);
        mkdir(FILE_PATH . "backups/" . $id . "/plugins");
        mkdir(FILE_PATH . "backups/" . $id . "/players");
        mkdir(FILE_PATH . "backups/" . $id . "/worlds");
        console("[TimeCapsule] File transfer started");
        //You can add or remove backup folders in the below lines
        console("Backing Up /plugins...");
        $this->recurse_copy(FILE_PATH . "plugins", FILE_PATH . "backups/" . $id . "/plugins", FILE_PATH . "backups/" . $data[0] . "/plugins", $data[1]);
        console("Backing Up /players...");
        $this->recurse_copy(FILE_PATH . "players", FILE_PATH . "backups/" . $id . "/players", FILE_PATH . "backups/" . $data[0] . "/players", $data[1]);
        console("Backing Up /worlds...");
        $this->recurse_copy(FILE_PATH . "worlds", FILE_PATH . "backups/" . $id . "/worlds", FILE_PATH . "backups/" . $data[0] . "/worlds", $data[1]);
        $data[0] = $data[0] + 1;
        $data[1] = strtotime("now");
        $this->api->plugin->writeYAML(FILE_PATH . "backups/data.yml", $data);
        console("[TimeCapsule] Backup Completed!");

    }
    public function restore($cmd, $params, $issuer, $alias)
    {
        if (isset($params[0])) {
            console("[TimeCapsule] Restore Started using backup with the ID " . $params[0]);
            $this->restore_copy(FILE_PATH . "backups/" . $params[0], FILE_PATH);
            console("[TimeCapsule] Restore Completed!");
        } else {
            console("[TimeCapsule] Backup not specified");
        }
    }
    public function allbackups($cmd, $params, $issuer, $alias)
    {
        $backups_dir = FILE_PATH . "backups/";
        $backup_files = array_diff(scandir($backups_dir, SCANDIR_SORT_DESCENDING), array('..', '.')); // sort by file modification time
        usort($backup_files, function ($a, $b) use ($backups_dir) {
            $a_time = filemtime($backups_dir . $a);
            $b_time = filemtime($backups_dir . $b);
            return $a_time - $b_time; // sort in descending order
        });
        $num_entries = isset($params[0]) ? intval($params[0]) : 40;
        $backup_files = array_slice($backup_files, count($backup_files) - $num_entries - 1, count($backup_files));
        foreach ($backup_files as $backup) {
            $backup_path = $backups_dir . '/' . $backup;
            if (is_dir($backup_path)) {
                $backup_datetime = date('Y-m-d H:i:s', filemtime($backup_path)); // get date/time of backup folder
                console($backup . ' (' . $backup_datetime . ')'); // print out backup folder name and date/time
            }
        }
    }
}
?>