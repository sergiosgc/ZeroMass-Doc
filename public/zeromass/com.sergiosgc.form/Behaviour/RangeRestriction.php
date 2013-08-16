<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class Behaviour_RangeRestriction implements Behaviour {
    public function __construct($min = null, $max = null, $step = null) {/*{{{*/
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;
    }/*}}}*/
    protected $min = null;/*{{{*/
    public function getMin() { return $min; }
    public function setMin($min) { $this->min = $min; }/*}}}*/
    protected $max = null;/*{{{*/
    public function getMax() { return $max; }
    public function setMax($max) { $this->min = $min; }/*}}}*/
    protected $step = null;/*{{{*/
    public function getStep() { return $step; }
    public function setStep($step) { $this->step = $step; }/*}}}*/
}
