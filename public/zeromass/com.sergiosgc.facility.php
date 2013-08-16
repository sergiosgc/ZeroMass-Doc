<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Facility {
    protected static $facilities = array();
    protected static $announcedFacilities = array();
    /**
     * Register to provide a facility
     *
     * Registration point where plugins register themselves to provide a commonly used API.
     * The APIs are identified by strings, which will be used by other plugins to fetch the 
     * facility (commonly used API). Examples of tags are `configuration`, `DB` or `cache`.
     *
     * Plugins provide an object following the facility API. It is out of the scope of this
     * plugin to avoid collision of facility names and APIs. It is expected that developers 
     * either namespace the tag of their facilities (e.g. `com.example.db`) or cooperate
     * towards unified APIs in the global namespace.
     *
     * @param string Tag
     * @param object Provider
     */
    public static function register($tag, $provider) {/*{{{*/
        if (!is_object($provider)) throw new FacilityInvalidArgumentException(sprintf('Provider set for %s is not an object', $tag));
        if (isset(self::$facilities[$tag])) {
            $candidates = array(self::$facilities[$tag], $provider);
            /*#
             * Allow plugins to handle facility registration collisions
             *
             * This hook gets fired if two plugins try to register for the same facility. 
             * If the collision is not handled, Facility will throw a FacilityDuplicateException.
             * Handling the collision means selecting one of the two providers, or replacing the 
             * two providers alltogether with a single one.
             *
             * @param array Array of two objects competing to provide the facility. Index zero is the first registered provider and index one is the one being registered now
             * @param string Tag for which registration is ocurring
             * @return array|object Either an array of facility providers or a single facility provider
             */
            $candidates = \ZeroMass::getInstance()->do_callback('com.sergiosgc.facility.collision', $candidates, $tag);
            if (is_array($candidates) && count($candidates) == 1) {
                $candidates = array_values($candidates);
                $candidates = $candidates[0];
            }
            if (!is_object($candidates)) throw new FacilityDuplicateException(sprintf('Duplicate Facility registration for %s not resolved by com.sergiosgc.facility.collision hook', $tag));
            $provider = $candidates;
        }
        self::$facilities[$tag] = $provider;
        if (!isset(self::$announcedFacilities[$tag])) {
            self::$announcedFacilities[$tag] = true;
            /*#
             * Let plugins know a new facility is now available
             *
             * @param string Tag for which a facility was now registered
             */
            \ZeroMass::getInstance()->do_callback('com.sergiosgc.facility.available', $tag);
            /*#
             * Let plugins know a new facility is now available
             */
            \ZeroMass::getInstance()->do_callback('com.sergiosgc.facility.available_' . $tag);
        } else {
            /*#
             * Let plugins know a facility has been updated
             *
             * @param string Tag for which a facility was now registered
             */
            \ZeroMass::getInstance()->do_callback('com.sergiosgc.facility.replaced', $tag);
            /*#
             * Let plugins know a facility has been updated
             */
            \ZeroMass::getInstance()->do_callback('com.sergiosgc.facility.replaced_' . $tag);
        }
    }/*}}}*/
    /**
     * Retrieve a facility
     *
     * Get a facility by tag name. If you are not handling the return of null values, leave
     * the second parameter `$exceptionIfNonExistent` at its default value (true). Setting
     * it to false will change the behaviour of the method in the case where the facility is
     * not registered: instead of throwing an exception, it will return null
     *
     * @param string Tag of the facility to fetch
     * @param boolean Whether to throw an exception if the facility does not exist. Optional. Defaults to true.
     */
    public static function get($tag, $exceptionIfNonExistent = true) {/*{{{*/
        if (!isset(self::$facilities[$tag])) {
            if ($exceptionIfNonExistent) throw new FacilityNotFoundException(sprintf('Facility not found for %s', $tag));
            return null;
        }
        return self::$facilities[$tag];
    }/*}}}*/
}

class FacilityException extends \Exception { }
class FacilityNotFoundException extends FacilityException { }
class FacilityDuplicateException extends FacilityException { }
class FacilityInvalidArgumentException extends FacilityException {}

/*#
 * API Facility directory service
 *
 * Provide a central point for plugins to register as provider of global facilities and for plugins to 
 * retrieve the provider of a global facility
 *
 * # Usage summary
 *
 * A global facility is a common API provider, like database access or configuration services. This 
 * plugin decouples users and providers of these common facilities, by means of a global registration
 * directory. It is expected that plugins providing a facility capture `com.sergiosgc.zeromass.pluginInit`
 * and register themselves as providers of a facility, using `Facility::register` while facility users
 * capture `com.sergiosgc.facility.available` and use `Facility::get` to use the facility.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
