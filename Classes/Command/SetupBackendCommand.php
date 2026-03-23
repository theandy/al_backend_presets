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
            ->setDescription('Erstellt Rollen: Admin, Editor, Redakteur + User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $groupConnection = $connectionPool->getConnectionForTable('be_groups');
        $userConnection = $connectionPool->getConnectionForTable('be_users');

        // =========================
        // Gruppen anlegen
        // =========================
        $editorGroupId = $this->createGroup($groupConnection, 'Editor', [
            'groupMods' => 'web_layout,web_list,file',
            'tables_select' => 'pages,tt_content,sys_file',
            'non_exclude_fields' => 'pages:*,tt_content:*',
        ], $output);

        $redakteurGroupId = $this->createGroup($groupConnection, 'Redakteur', [
            'groupMods' => 'web_layout',
            'tables_select' => 'pages,tt_content',
            'non_exclude_fields' => 'pages:title,hidden;tt_content:header,bodytext',
        ], $output);

        // =========================
        // User erstellen
        // =========================
        $this->createUser($userConnection, 'editor', 'editor123', $editorGroupId, 'Editor User', $output);
        $this->createUser($userConnection, 'redakteur', 'redakteur123', $redakteurGroupId, 'Redakteur User', $output);

        // Optional Admin User
        $this->createAdminUser($userConnection, 'admin2', 'admin123', $output);

        return Command::SUCCESS;
    }

    // =========================
    // Gruppe erstellen
    // =========================
    private function createGroup($connection, string $title, array $config, OutputInterface $output): int
    {
        $existing = $connection->select(
            ['uid'],
            'be_groups',
            ['title' => $title]
        )->fetchOne();

        if ($existing) {
            $output->writeln("Gruppe \"$title\" existiert bereits.");
            return (int)$existing;
        }

        $connection->insert('be_groups', [
            'title' => $title,
            'groupMods' => $config['groupMods'],
            'tables_select' => $config['tables_select'],
            'non_exclude_fields' => $config['non_exclude_fields'],
            'explicit_allowdeny' => 1,
            'pagetypes_select' => '1',
            'tsconfig' => '
options.clearCache.pages = 1
',
        ]);

        $output->writeln("Gruppe \"$title\" wurde erstellt.");

        return (int)$connection->lastInsertId();
    }

    // =========================
    // User erstellen
    // =========================
    private function createUser($connection, string $username, string $password, int $groupId, string $realName, OutputInterface $output): void
    {
        $existing = $connection->select(
            ['uid'],
            'be_users',
            ['username' => $username]
        )->fetchOne();

        if ($existing) {
            $output->writeln("User \"$username\" existiert bereits.");
            return;
        }

        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE');

        $passwordHash = $hashInstance->getHashedPassword($password);

        $connection->insert('be_users', [
            'username' => $username,
            'password' => $passwordHash,
            'admin' => 0,
            'usergroup' => $groupId,
            'realName' => $realName,
            'email' => $username . '@example.com',
            'disable' => 0,
            'db_mountpoints' => '1',
        ]);

        $output->writeln("User \"$username\" wurde erstellt.");
    }

    // =========================
    // Admin User
    // =========================
    private function createAdminUser($connection, string $username, string $password, OutputInterface $output): void
    {
        $existing = $connection->select(
            ['uid'],
            'be_users',
            ['username' => $username]
        )->fetchOne();

        if ($existing) {
            $output->writeln("Admin \"$username\" existiert bereits.");
            return;
        }

        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE');

        $passwordHash = $hashInstance->getHashedPassword($password);

        $connection->insert('be_users', [
            'username' => $username,
            'password' => $passwordHash,
            'admin' => 1,
            'realName' => 'Admin User',
            'email' => 'admin@example.com',
            'disable' => 0,
        ]);

        $output->writeln("Admin \"$username\" wurde erstellt.");
    }
}