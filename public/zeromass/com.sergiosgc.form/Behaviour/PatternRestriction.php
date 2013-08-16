<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class Behaviour_PatternRestriction implements Behaviour {
    protected $pattern;/*{{{*/
    public function getPattern() { return $this->pattern; }
    public function setPatter($pattern) { $this->pattern = $pattern; }/*}}}*/
    public function __construct($pattern) {
        $this->pattern = $pattern;
    }
    public function match($string) {/*{{{*/
        return 1 == preg_match('_' . strtr($pattern, array( '_' => '\\_' )) . '_', $string);
    }/*}}}*/
}
