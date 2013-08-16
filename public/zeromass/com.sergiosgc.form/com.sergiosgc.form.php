<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

class Form implements \Iterator{
    protected $fields = array();
    protected $action;
    protected $method;
    protected static $registeredAutoload = false;
    protected $submitTargets = array();

    public static function autoloader($class) {/*{{{*/
        if (strlen($class) < strlen(__NAMESPACE__) || __NAMESPACE__ != substr($class, 0, strlen(__NAMESPACE__))) return;
        $class = substr($class, strlen(__NAMESPACE__) + 1);
        $path = dirname(__FILE__) . '/' . strtr($class, array('_' => '/')) . '.php';
        if (file_exists($path) && is_readable($path)) require_once($path);
    }/*}}}*/
    public static function registerAutoloader() {/*{{{*/
        if (!self::$registeredAutoload) {
            spl_autoload_register(array(__CLASS__, 'autoloader'));
            self::$registeredAutoload = true;
        }
    }/*}}}*/
    public function __construct($action, $method = 'POST') {/*{{{*/
        $this->setAction($action);
        $this->setMethod($method);
    }/*}}}*/
    public function addSubmitTarget($key, $label) {/*{{{*/
        $this->submitTargets[$key] = $label;
    }/*}}}*/
    public function removeSubmitTarget($key) {/*{{{*/
        unset($this->submitTargets[$key]);
    }/*}}}*/
    public function getSubmitTargets() {/*{{{*/
        if (count($this->submitTargets) == 0) return array( 'submit' => __('Submit') );
        return $this->submitTargets;
    }/*}}}*/
    public function getAction() {/*{{{*/
        return $this->action;
    }/*}}}*/
    public function setAction($action) {/*{{{*/
        $this->action = $action;
    }/*}}}*/
    public function getMethod() {/*{{{*/
        return $this->method;
    }/*}}}*/
    public function setMethod($method) {/*{{{*/
        $this->method = $method;
    }/*}}}*/
    public function addField($field) {/*{{{*/
        if (is_object($field) && ! $field instanceof Field) throw new Exception('Invalid field parameter');
        if (!is_object($field)) $field = new Field($field);
        $this->fields[] = $field;
    }/*}}}*/
    public function getFieldIndex($field, $exceptionIfMissing = false) {/*{{{*/
        if (is_object($field) && ! $field instanceof Field) throw new Exception('Invalid field parameter');
        if (is_object($field)) $field = $field->getName();

        foreach (array_keys($this->fields) as $key) if ($this->fields[$key]->getName() == $field) return $key;
        if ($exceptionIfMissing) throw new Exception(sprintf('Field %s not found', $field));
        return null;
    }/*}}}*/
    public function getField($field, $exceptionIfMissing = false) {/*{{{*/
        return $this->fields[$this->getFieldIndex($field, $exceptionIfMissing)];
    }/*}}}*/
    public function removeField($field, $exceptionIfMissing = false) {/*{{{*/
        $index = $this->getFieldIndex($field, $exceptionIfMissing);
        if (is_null($index)) return false;
        unset($this->fields[$index]);
        $this->fields = array_values($index);
        return true;
    }/*}}}*/
    public function replaceField($replaceThis, $withThis) {/*{{{*/
        $index = $this->getFieldIndex($replaceThis, true);
        $this->fields[$index] = $withThis;
    }/*}}}*/
    public function addFieldAfter($addThis, $afterThis) {/*{{{*/
        $index = $this->getFieldIndex($afterThis, true);
        $result = array_slice($this->fields, 0, $index+1);
        $result[] = $addThis;
        if ($index < count($this->fields)) $result = array_merge($result, array_slice($this->fields, $index+1));

        $this->fields = $result;
    }/*}}}*/
    public function addFieldBefore($addThis, $afterThis) {/*{{{*/
        $index = $this->getFieldIndex($afterThis, true);
        $result = array();
        if ($index > 0) $result = array_slice($this->fields, 0, $index);
        $result[] = $addThis;
        $result = array_merge($result, array_slice($this->fields, $index));

        $this->fields = $result;
    }/*}}}*/
    public function sortFields($callable = null) {/*{{{*/
        if (is_callable($callable)) {
            $this->fields = call_user_func($callable, $this->fields, $this);
        }

        /*#
         * Form fields have just been sorted. Allow the result to be mangled
         *
         * @param array Array of com.sergiosgc.field
         * @return array Sorted array of com.sergiosgc.field
         */
        $this->fields = zm_fire('com.sergiosgc.form.sort', $this->fields);
    }/*}}}*/

    // Iterator interface implementation/*{{{*/
    public function current () {
        return current($this->fields);
    }
    public function key () {
        return key($this->fields);
    }
    public function next () {
        return next($this->fields);
    }
    public function rewind () {
        reset($this->fields);
    }
    public function valid () {
        $key = key($this->fields);
        return $key !== NULL && $key !== false;
    }/*}}}*/
}

Form::registerAutoloader();
