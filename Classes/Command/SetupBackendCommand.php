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
            'tables_select' => 'pages,tt_content,sys_file',
            'non_exclude_fields' => 'pages:*,tt_content:*',
            'explicit_allowdeny' => 1,
            'allowed_modules' => 'web_layout,web_list,file_Filelist',
            'pagetypes_select' => '1',
        ]);

        $output->writeln('Gruppe "Editor" wurde erstellt.');

        return Command::SUCCESS;
    }
}