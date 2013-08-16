<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\config\ini;

/**
 * com.sergiosgc.config driver that uses INI files
 */
class Config {
    protected static $singleton = null;
    protected $config = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'), 5);
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('config', $this);
    }/*}}}*/
    protected function _set(&$config, $key, $value) {/*{{{*/
        $dotPos = strpos($key, '.');
        if ($dotPos === false) {
            $config[$key] = $value;
            return;
        }
        $left = substr($key, 0, $dotPos);
        $right = substr($key, $dotPos + 1);
        if (!isset($config[$left])) $config[$left] = array();
        $this->_set($config[$left], $right, $value);
    }/*}}}*/
    protected function readConfig() {/*{{{*/
        if (!is_null($this->config)) return;
        $configFilePath = \ZeroMass::getInstance()->privateDir . '/config.ini';
        /*#
         * Allow the default config file path to be mangled
         *
         * @param string Config file absolute path
         * @return string Config file absolute path
         */
        $configFilePath = \ZeroMass::getInstance()->do_callback('com.sergiosgc.config.ini.configFilePath', $configFilePath);
        if (!file_exists($configFilePath)) throw new ConfigException(sprintf('Configuration file not found: %s', $configFilePath));
        if (!is_readable($configFilePath)) throw new ConfigException(sprintf('Configuration file is not readable: %s', $configFilePath));
        $config = parse_ini_file($configFilePath, true, INI_SCANNER_NORMAL);
        if ($config === false) throw new ConfigException(sprintf('Error parsing config file: %s', $configFilePath));
        $deepConfig = array();
        foreach ($config as $key => $value) $this->_set($deepConfig, $key, $value);
        $config = $deepConfig;

        /*# 
         * The configuration has been read. Allow it to be mangled.
         *
         * @param array Configuration, as a tree of keys, where leafs are config value
         * @return array Configuration, as a tree of keys, where leafs are config value
         */
        $config = \ZeroMass::getInstance()->do_callback('com.sergiosgc.config.ini.config', $config);
        $this->config = $config;
    }/*}}}*/
    /**
     * Get a configuration value
     *
     * @param string key Configuration key
     * @param boolean Whether to throw an exception if configuration key not found. Optional, defaults to true
     * @param mixed Default value, should an exception not be thrown when configuration key not found. Optional, defaults to null
     */
    public function get($key, $exceptionIfNotFound = true, $default = null) {/*{{{*/
        $this->readConfig();
        $parts = explode('.', $key);
        $result = $this->config;
        foreach ($parts as $part) {
            if (!isset($result[$part])) {
                if ($exceptionIfNotFound) throw new KeyNotFoundException(sprintf('Did not find configuration key %s while looking for %s', $key, $part));
                return $default;
            }
            $result = $result[$part];
        }
        return $result;
    }/*}}}*/
    /**
     * Get subkeys immediately below a given key
     *
     * @param string key Configuration key
     * @param boolean Whether to throw an exception if configuration key not found. Optional, defaults to true
     * @param mixed Default value, should an exception not be thrown when configuration key not found. Optional, defaults to null
     */
    public function getKeys($key, $exceptionIfNotFound = true, $default = null) {/*{{{*/
        $result = $this->get($key, $exceptionIfNotFound, $default);
        if (!is_array($result) && $exceptionIfNotFound) throw new KeyNotFoundException(sprintf('Configuration under %s has no subkeys', $key));
        if (!is_array($result)) return $default;
        $result = array_keys($result);
        return $result;
    }/*}}}*/
    /**
     * Set a configuration key
     *
     * This plugin is read-only, so this method throws a ConfigException if called
     *
     * @param string Configuration key
     * @param string Configuration value
     */
    public function set($key, $value) {/*{{{*/
        throw new ConfigException('\com\sergiosgc\config\ini\Config is read only');
    }/*}}}*/
    /**
     * Report this plugin as read-only
     * @return boolean false, stating this config driver is read-only
     */
    public function isWritable() {/*{{{*/
        return false;
    }/*}}}*/
}

class ConfigException extends \Exception { }
class KeyNotFoundException extends ConfigException { }

Config::getInstance();

/*#
 * Configuration driver for ini files
 *
 * Configuration facility provider that reads its configuration from private/config.ini.
 *
 * # Usage summary 
 *
 * This is a webapp configuration provider, that reads its configuration from a .ini file.
 *
 * The ini file is, by default, looked for in private/config.ini. You may change the location
 * by hooking up to the `com.sergiosgc.config.ini.path` hook.
 *
 * The file is parsed using [parse_ini_file](http://php.net/manual/en/function.parse-ini-file.php)
 * so it follows the php.ini conventions.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
