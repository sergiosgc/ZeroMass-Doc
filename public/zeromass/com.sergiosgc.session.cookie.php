<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\session;

class Cookie {
    protected static $singleton = null;
    protected $values = null;
    protected $secret = null;
    protected $registeredFacility = false;
    protected $idleLifetime = 7200 /* 2 hours */;
    protected $cookiePrefix = 'com_sergiosgc_';
    /**
     * Singleton pattern instance getter
     * @return Cookie The singleton Cookie
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
    }/*}}}*/
    public function config() {/*{{{*/
        $this->idleLifetime = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.session.cookie.idleLifetime', false, $this->idleLifetime);
        /*#
         * Filter the cookie idleLifetime, used as default time to allow cookies to remain idle (not participating in requests)
         *
         * @param string The cookie idleLifetime
         * @return string The cookie idleLifetime
         */
        $this->idleLifetime = \ZeroMass::getInstance()->do_callback('com.sergiosgc.session.cookie.idlelifetime', $this->idleLifetime);
        $this->secret = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.session.cookie.secret', true, $this->secret);
        /*#
         * Filter the cookie secret, used to hash cookie contents against tampering
         *
         * @param string The cookie secret
         * @return string The cookie secret
         */
        $this->secret = \ZeroMass::getInstance()->do_callback('com.sergiosgc.session.cookie.secret', $this->secret);
        if (!is_null($this->secret) && !$this->registeredFacility) {
            $this->registeredFacility = true;
            $this->loadCookies();
            \com\sergiosgc\Facility::register('session', $this);
        }
    }/*}}}*/
    public function getHashSecret() {/*{{{*/
        if (is_null($this->secret)) {
            $this->config();
            if (is_null($this->secret)) throw new Exception('Cookie hash secret not set via config key com.sergiosgc.session.cookie.secret nor via hook com.sergiosgc.session.cookie.secret');
            $this->loadCookies();
            \com\sergiosgc\Facility::register('session', $this);
        }
        return $this->secret;
    }/*}}}*/
    public function getCookiePrefix() {/*{{{*/
        return $this->cookiePrefix;
    }/*}}}*/
    public function getIdleLifetime() {/*{{{*/
        return $this->idleLifetime;
    }/*}}}*/
    public function generateSecureCookie($data) {/*{{{*/
        $cookieData = array(
            'data' => serialize($data)
        );
        $cookieData['hash'] = md5($cookieData['data'], $this->getHashSecret());
        return base64_encode(serialize($cookieData));
    }/*}}}*/
    public function decodeSecureCookie($cookie) {/*{{{*/
        $cookieData = base64_decode($cookie);
        if ($cookieData === false) return false;
        $cookieData = @unserialize($cookieData);
        if ($cookieData === false) return false;
        $hash = md5($cookieData['data'], $this->getHashSecret());
        if ($cookieData['hash'] != $hash) return false;
        $cookieData = $cookieData['data'];
        return $cookieData;
    }/*}}}*/
    public function setCookie($name) {/*{{{*/
        setcookie($this->getCookiePrefix() . $name,
            $this->generateSecureCookie($this->values[$name]),
            time() + $this->values[$name]['lifetime'],
            '/', 
            false,
            false);
    }/*}}}*/
    public function set($name, $value, $idleLifetime = null) {/*{{{*/
        if (is_null($idleLifetime)) $idleLifetime = $this->getIdleLifetime();
        $value = array(
            'name' => $name,
            'value' => $value,
            'lifetime' => $idleLifetime,
            'timestamp' => time()
        );
        $this->setValue($value);
        $this->setCookie($name);
    }/*}}}*/
    public function delete($name) { /*{{{*/
        $this->deleteValue($name);
        setcookie($this->getCookiePrefix() . $name, '', 1, '/', false, true);
    }/*}}}*/
    public function get($name, $exceptionIfNotFound = false, $default = null) {/*{{{*/
        $value = $this->getValue($name);
        if ($value && time() < ($value['timestamp'] + $value['lifetime'])) {
            return $value['value'];
        }
        if ($exceptionIfNotFound) throw new Exception(sprintf('Session variable %s not found', $name));
        return $default;
    }/*}}}*/
    public function loadCookies() {/*{{{*/
        $this->values = array();
        foreach ($_COOKIE as $cookie) {
            $value = $this->decodeSecureCookie($cookie);
            if (!$value) continue; // Not one of our cookies, or invalid cookie security
            $value = unserialize($value);
            if ($value === false) throw new Exception('Unable to unserialize cookie ' . $cookie);
            if (time() > ($value['timestamp'] + $value['lifetime'])) { // Expired
                @setcookie($this->getCookiePrefix() . $value['name'], '', 1, '/', false, true); // Error-silenced because it may be too late to send cookies
                continue;
            }
            $this->setValue($value);
            @$this->setCookie($value['name']); // Refresh cookie lifetime on browser, error-silenced because it may be too late to send cookies
        }
    }/*}}}*/
    public function getValue($name) {/*{{{*/
        if (is_null($this->values)) $this->loadCookies();
        if (!isset($this->values[$name])) return false;
        return $this->values[$name];
    }/*}}}*/
    public function setValue($value) {/*{{{*/
        if (is_null($this->values)) $this->loadCookies();
        $this->values[$value['name']] = $value;
    }/*}}}*/
    public function deleteValue($name) {/*{{{*/
        if (is_null($this->values)) $this->loadCookies();
        unset($this->values[$name]);
    }/*}}}*/
}
Cookie::getInstance();
class Exception extends \Exception { }
