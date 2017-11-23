<?php

namespace Phue\Schema\Changes;

use Exception;
use Phue\Schema\SchemaChange;
use Phue\User\UserProvider;
use Phue\User\User;

class Change_20171122_1000 extends SchemaChange
{

    /**
     * Applies the version
     *
     * @return bool TRUE on success
     * @throws Exception When admin account is not configured
     * @todo consider backup dump for loss-less re-applying?
     */
    public function apply()
    {
        // make sure admin account exists
        $adminAccount = $this->app->config->get('_adminUser');
        if (!$adminAccount) {
            throw new Exception('Please configure an administrator (e.g. "_adminUser": "mail@example.com")');
        }

        return
            $this->initChangeTable() &&
            $this->initUserTable() &&
            $this->initAdminAccount();
    }

    /**
     * Creates a table for schema changes
     *
     * @return bool TRUE on success
     */
    protected function initChangeTable()
    {
        $sql = '
          CREATE TABLE IF NOT EXISTS `Change` (
            `name` TEXT,
            `applied` INTEGER
          );
        ';
        return $this->executeSql($sql, 'schema');
    }

    /**
     * Creates a table for user accounts
     *
     * @return bool TRUE on success
     */
    protected function initUserTable()
    {
        $sql = '
          CREATE TABLE IF NOT EXISTS `User` (
            `username` TEXT UNIQUE NOT NULL,
            `password` TEXT,
            `roles` TEXT,
            `enabled` INTEGER NOT NULL DEFAULT 1,
            `expired` INTEGER NOT NULL DEFAULT 0,
            `credentialsExpired` INTEGER NOT NULL DEFAULT 0,
            `locked` INTEGER NOT NULL DEFAULT 0
          );
        ';
        return $this->executeSql($sql, 'users');
    }

    /**
     * Creates the initial admin account with an empty password
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    protected function initAdminAccount()
    {
        // create user account with empty password
        $adminUserName = $this->app->config->get('_adminUser');
        $user = new User($adminUserName, $this->app->users->encodePassword(''), ['admin']);
        return $this->app->users->saveUser($user);
    }

    /**
     * Reverts the schema migration
     *
     * @return bool
     * @todo create backup dump for loss-less re-applying?
     */
    public function revert()
    {
        // drop User table
        $this->executeSql('DROP TABLE IF EXISTS `User`', 'users');

        // drop Change table
        return $this->executeSql('DROP TABLE IF EXISTS `Change`', 'schema');
    }
}
