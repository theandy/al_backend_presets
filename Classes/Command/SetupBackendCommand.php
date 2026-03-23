<?php

namespace Vendor\AlBackendPresets\Command;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBackendCommand extends Command
{
    protected function configure()
    {
        $this->setName('albackendpresets:setup')
            ->setDescription('Erstellt Backend-Gruppe + User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $groupConnection = $connectionPool->getConnectionForTable('be_groups');
        $userConnection = $connectionPool->getConnectionForTable('be_users');

        $groupName = 'Editor';

        // =========================
        // Gruppe prüfen / erstellen
        // =========================
        $groupUid = $groupConnection->select(
            ['uid'],
            'be_groups',
            ['title' => $groupName]
        )->fetchOne();

        if (!$groupUid) {
            $groupConnection->insert('be_groups', [
                'title' => $groupName,

                // ✅ Module (entscheidend!)
                'groupMods' => 'web_layout,web_list,media_management',

                // Tabellenzugriff
                'tables_select' => 'pages,sys_file_reference,tt_content',

                // Feldrechte
                'non_exclude_fields' => 'sys_file_reference:crop,
                sys_file_reference:title,
                tt_content:image_zoom,
                tt_content:hidden',

                // Rechte aktivieren
                // 'explicit_allowdeny' => 1,

                'explicit_allowdeny' => '
                tt_content:CType:header,
                tt_content:CType:image,
                tt_content:CType:textmedia',

                // Seitentypen
                'pagetypes_select' => '1',

                // Optionales TSconfig (nur Verhalten!)
                'tsconfig' => '
options.clearCache.pages = 1
',

                'description' => 'automatisch erstellt. nicht ändern.'

            ]);

            $groupUid = $groupConnection->lastInsertId();
            $output->writeln('Gruppe "Editor" wurde erstellt.');
        } else {
            $output->writeln('Gruppe existiert bereits.');
        }

        // =========================
        // User prüfen / erstellen
        // =========================
        $username = 'editor';

        $existingUser = $userConnection->select(
            ['uid'],
            'be_users',
            ['username' => $username]
        )->fetchOne();

        if ($existingUser) {
            $output->writeln('User "editor" existiert bereits.');
            return Command::SUCCESS;
        }

        // Passwort hashen
        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE');

        $passwordHash = $hashInstance->getHashedPassword('editor123');

        // User anlegen
        $userConnection->insert('be_users', [
            'username' => $username,
            'password' => $passwordHash,
            'admin' => 0,
            'usergroup' => $groupUid,
            'realName' => 'Editor User',
            'email' => 'editor@example.com',
            'disable' => 0,

            // Optional sinnvoll:
            'db_mountpoints' => '1',
        ]);

        $output->writeln('User "editor" wurde erstellt (Passwort: editor123).');

        return Command::SUCCESS;
    }
}