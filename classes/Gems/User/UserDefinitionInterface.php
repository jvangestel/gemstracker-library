<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\User\Group;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface Gems_User_UserDefinitionInterface
{
    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems_User_User $user = null);

    /**
     * Return true if the two factor can be set.
     *
     * @return boolean
     */
    public function canSaveTwoFactorKey();

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition when no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems_User_User $user = null);

    /**
     * Returns an initialized Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password);

    /**
     * Return a password reset key
     *
     * @param \Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems_User_User $user);

    /**
     * Returns the number of hours a reset key remains valud
     *
     * @return int
     */
    public function getResetKeyDurationInHours();

    /**
     * Returns the data for a user object. It may be empty if the user is unknown.
     *
     * @param string $login_name
     * @param int $organization
     * @return array Of data to fill the user with.
     */
    public function getUserData($login_name, $organization);

    /**
     * Return true if the user has a password.
     *
     * @param \Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems_User_User $user);

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return boolean
     */
    public function isStaff();

    /**
     * Should this user be authorized using two factor authentication?
     *
     * @param string $ipAddress
     * @param boolean $hasKey
     * @param Group $group
     * @return boolean
     */
    public function isTwoFactorRequired($ipAddress, $hasKey, Group $group = null);

    /**
     * Set the password, if allowed for this user type.
     *
     * @param \Gems_User_User $user The user whose password to change
     * @param string $password
     * @return \Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems_User_User $user, $password);

    /**
     *
     * @param \Gems_User_User $user The user whose password to change
     * @param string $newKey
     * @return $this
     */
    public function setTwoFactorKey(\Gems_User_User $user, $newKey);
}
