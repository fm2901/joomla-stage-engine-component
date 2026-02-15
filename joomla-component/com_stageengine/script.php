<?php

/**
 * @package     Joomla.Component
 * @subpackage  com_stageengine
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Installer\InstallerAdapter;

final class Com_StageengineInstallerScript
{
    public function install(InstallerAdapter $parent): bool
    {
        $this->runMigrations();

        return true;
    }

    public function update(InstallerAdapter $parent): bool
    {
        $this->runMigrations();

        return true;
    }

    private function runMigrations(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $sqlFile = __DIR__ . '/sql/install.mysql.utf8.sql';

        if (!File::exists($sqlFile)) {
            throw new RuntimeException('SQL migration file not found: ' . $sqlFile);
        }

        $sql = (string) file_get_contents($sqlFile);
        $sql = trim($sql);

        if ($sql === '') {
            return;
        }

        $queries = preg_split('/;\s*[\r\n]+/', $sql) ?: [];

        foreach ($queries as $query) {
            $query = trim($query);

            if ($query === '') {
                continue;
            }

            $db->setQuery($db->replacePrefix($query));
            $db->execute();
        }
    }
}
