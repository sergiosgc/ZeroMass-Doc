<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class Field {
    protected $label = null;/*{{{*/
    public function getLabel() {
        if (is_null($this->label)) return $this->getName();
        return $this->label;
    }
    public function setLabel($label) {
        $this->label = $label;
    }/*}}}*/
    protected $name;/*{{{*/
    public function getName() {
        return $this->name;
    }
    public function setName($name) {
        $this->name = $name;
    }/*}}}*/
    protected $value;/*{{{*/
    public function getValue() {
        return $this->value;
    }
    public function setValue($value) {
        $this->value = $value;
    }/*}}}*/
    protected $placeholderText = null;/*{{{*/
    public function getPlaceholderText() {
        return is_null($this->placeholderText) ? '' : $this->placeholderText;
    }
    public function setPlaceholderText($placeholderText) {
        $this->placeholderText = $placeholderText;
    }/*}}}*/
    protected $helpText = null;/*{{{*/
    public function getHelpText() {
        return is_null($this->helpText) ? '' : $this->helpText;
    }
    public function setHelpText($helpText) {
        $this->helpText = $helpText;
    }/*}}}*/
    protected $errorText = null;/*{{{*/
    public function getErrorText() {
        return is_null($this->errorText) ? '' : $this->errorText;
    }
    public function setErrorText($errorText) {
        $this->errorText = $errorText;
    }/*}}}*/
    protected $behaviours = array();/*{{{*/
    public function addBehaviour($behaviour) {
        if (is_string($behaviour)) {
            if ($behaviour[0] != '\\') $behaviour = '\\com\\sergiosgc\\form\\' . $behaviour;
            $behaviour = new $behaviour();
        }
        $this->behaviours[] = $behaviour;
    }
    public function removeBehaviour($behaviour) {
        foreach(array_reverse(array_keys($this->behaviours)) as $i) if ($this->behaviours[$i] == $behaviour) unset($this->behaviour[$i]);
    }
    public function getBehaviours() {
        return $this->behaviours;
    }
    public function getBehavioursByClass($class) {
        if ($class[0] != '\\') $class = '\\com\\sergiosgc\\form\\' . $class;
        $result = array();

        foreach ($this->behaviours as $b) if ($b instanceof $class) $result[] = $b;
        return $result;
    }
    public function hasBehaviourByClass($class) {
        return 0 != count($this->getBehavioursByClass($class));
    }/*}}}*/


    public function __construct($name) {
        $this->setName($name);
    }
}
