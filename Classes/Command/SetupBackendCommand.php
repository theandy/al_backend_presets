<?php

namespace Vendor\AlBackendPresets\Command;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBackendCommand extends Command
{
    protected function configure()
    {
        $this->setName('albackendpresets:setup')
            ->setDescription('Erstellt Backend-Gruppen (Editor)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_groups');

        $groupName = 'Editor';

        $existing = $connection->select(
            ['uid'],
            'be_groups',
            ['title' => $groupName]
        )->fetchOne();

        if ($existing) {
            $output->writeln('Gruppe existiert bereits.');
            return Command::SUCCESS;
        }

        $connection->insert('be_groups', [
            'title' => $groupName,

            // Tabellenzugriff
            'tables_select' => 'pages,tt_content,sys_file',

            // Feldrechte
            'non_exclude_fields' => 'pages:*,tt_content:*',

            // Pflicht für Rechtehandling
            'explicit_allowdeny' => 1,

            // Seitentypen
            'pagetypes_select' => '1',

            // TYPO3 12: Module über TSconfig
            'tsconfig' => '
mod.web_layout.enable = 1
mod.web_list.enable = 1
mod.file.enable = 1
',
        ]);

        $output->writeln('Gruppe "Editor" wurde erstellt.');

        return Command::SUCCESS;
    }
}