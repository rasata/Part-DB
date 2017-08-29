<?php
/*
    part-db version 0.1
    Copyright (C) 2005 Christoph Lechner
    http://www.cl-projects.de/

    part-db version 0.2+
    Copyright (C) 2009 K. Jacobs and others (see authors.php)
    http://code.google.com/p/part-db/

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

namespace PartDB;

/**
 * @todo
 *   Soll der SysAdmin einen Datenbankeintrag haben? Mit Admin-Gruppe?
 *   Oder sollen die Rechte des Admins hardgecoded sein (ID = 0) (wie bei "StructuralDBElement")?
 *   Zweiteres wäre theoretisch schöner, da man die Adminrechte nicht verlieren kann durch eine
 *   kaputte Datenbank. Allerdings müsste das Admin-Passwort dann irgendwo gespeichert werden,
 *   wo man es auch bequem wieder ändern kann, vielleicht in $config (config.php)?
 *   Da momentan andere Sachen eine höhere Priorität haben als die Benutzerverwaltung,
 *   lasse ich das hier einfach mal so stehen, das kann man dann anschauen sobald es gebraucht wird.
 *   kami89
 */

use Exception;
use PartDB\Interfaces\ISearchable;

/**
 * @file User.php
 * @brief class User
 *
 * @class User
 * All elements of this class are stored in the database table "users".
 * @author kami89
 */
class User extends Base\NamedDBElement implements ISearchable
{
    /********************************************************************************
     *
     *   Calculated Attributes
     *
     *   Calculated attributes will be NULL until they are requested for first time (to save CPU time)!
     *   After changing an element attribute, all calculated data will be NULLed again.
     *   So: the calculated data will be cached.
     *
     *********************************************************************************/

    /** @var Group the group of this user */
    private $group = null;

    /********************************************************************************
     *
     *   Constructor / Destructor / reset_attributes()
     *
     *********************************************************************************/

    /**
     * Constructor
     *
     * @param Database      &$database      reference to the Database-object
     * @param User|NULL     &$current_user  @li reference to the current user which is logged in
     *                                      @li NULL if $id is the ID of the current user
     * @param Log           &$log           reference to the Log-object
     * @param integer       $id             ID of the user we want to get
     *
     * @throws Exception    if there is no such user in the database
     * @throws Exception    if there was an error
     */
    public function __construct(&$database, &$current_user, &$log, $id)
    {
        if (! is_object($current_user)) {     // this is that you can create an User-instance for first time
            $current_user = $this;
        }           // --> which one was first: the egg or the chicken? :-)

        //parent::__construct($database, $current_user, $log, 'users', $id);
    }

    /**
     * @copydoc DBElement::reset_attributes()
     */
    public function reset_attributes($all = false)
    {
        $this->group = null;

        parent::reset_attributes($all);
    }

    /********************************************************************************
     *
     *   Getters
     *
     *********************************************************************************/

    /**
     * Get the group of this user
     *
     * @return Group        the group of this user
     *
     * @throws Exception    if there was an error
     */
    public function get_group()
    {
        if (! is_object($this->group)) {
            $this->group = new Group(
                $this->database,
                $this->current_user,
                $this->log,
                $this->db_data['group_id']
            );
        }

        return $this->group;
    }

    /**
     * Gets the username of the User.
     * @return string The username.
     */
    public function get_name()
    {
        return $this->db_data['name'];
    }

    /**
     * Gets the first name of the user.
     * @return string The first name.
     */
    public function getFirstName()
    {
        return $this->db_data['first_name'];
    }

    /**
     * Gets the last name of the user.
     * @return string The first name.
     */
    public function getLastName()
    {
        return $this->db_data['last_name'];
    }

    /**
     * Gets the email address of the user.
     * @return string The email address.
     */
    public function getEmail()
    {
        return $this->db_data['last_name'];
    }

    /**
     * Gets the department of the user.
     * @return string The department of the user.
     */
    public function getDepartment()
    {
        return $this->db_data['department'];
    }

    /**
     * Checks if a given password, is valid for this account.
     * @param $password string The password which should be checked.
     */
    public function isPasswordValid($password)
    {
        $hash = $this->db_data['password'];
        return password_verify($password, $hash);
    }

    /********************************************************************************
     *
     *   Setters
     *
     *********************************************************************************/

    /**
     * Change the group ID of this user
     *
     * @param integer $new_group_id     the ID of the new group
     *
     * @throws Exception if the new group ID is not valid
     * @throws Exception if there was an error
     */
    public function set_group_id($new_group_id)
    {
        $this->set_attributes(array('group_id' => $new_group_id));
    }

    /**
     * Sets a new password, for the User.
     * @param $new_password string The new password.
     */
    public function setPassword($new_password)
    {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $this->set_attributes(array("password" => $hash));
    }

    /**
     * Set a new first name.
     * @param $new_first_name string The new first name.
     */
    public function setFirstName($new_first_name)
    {
        $this->set_attributes(array('first_name' => $new_first_name));
    }

    /**
     * Set a new first name.
     * @param $new_first_name string The new first name.
     */
    public function setLastName($new_last_name)
    {
        $this->set_attributes(array('last_name' => $new_last_name));
    }


    /********************************************************************************
     *
     *   Static Methods
     *
     *********************************************************************************/

    /**
     * @copydoc DBElement::check_values_validity()
     */
    public static function check_values_validity(&$database, &$current_user, &$log, &$values, $is_new, &$element = null)
    {
        // first, we let all parent classes to check the values
        parent::check_values_validity($database, $current_user, $log, $values, $is_new, $element);

        // check "group_id"
        try {
            $group = new Group($database, $current_user, $log, $values['group_id']);
        } catch (Exception $e) {
            debug(
                'warning',
                _('Ungültige "group_id": "').$values['group_id'].'"'.
                _("\n\nUrsprüngliche Fehlermeldung: ").$e->getMessage(),
                __FILE__,
                __LINE__,
                __METHOD__
            );
            throw new Exception(_('Die gewählte Gruppe existiert nicht!'));
        }
    }



    /**
     * Get count of users
     *
     * @param Database &$database   reference to the Database-object
     *
     * @return integer              count of users
     *
     * @throws Exception            if there was an error
     */
    public static function get_count(&$database)
    {
        if (!$database instanceof Database) {
            throw new Exception(_('$database ist kein Database-Objekt!'));
        }

        return $database->get_count_of_records('users');
    }

    /**
     * Search elements by name.
     *
     * @param Database &$database reference to the database object
     * @param User &$current_user reference to the user which is logged in
     * @param Log &$log reference to the Log-object
     * @param string $keyword the search string
     * @param boolean $exact_match @li If true, only records which matches exactly will be returned
     * @li If false, all similar records will be returned
     *
     * @return array    all found elements as a one-dimensional array of objects,
     *                  sorted by their names
     *
     * @throws Exception if there was an error
     */
    public static function search(&$database, &$current_user, &$log, $keyword, $exact_match)
    {
        return parent::search_table($database, $current_user, $log, "user", $keyword, $exact_match);
    }

    public static function getUserByName(&$database, &$current_user, &$log, $username)
    {
        self::search($database, $current_user, $log, $username, true);
    }
}
