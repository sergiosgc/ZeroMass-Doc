<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

class Serializer_Bootstrap_Email extends Serializer_Bootstrap_Input{
    protected $type = 'email';
    public static function getFitnessForField($field, $form) {
        return $field->hasBehaviourByClass('Behaviour_EmailRestriction') ? 2 : 0;
    }
}

