<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

class Serializer_Bootstrap {
    protected $layout = null;
    protected static $inputClasses = array(
        '\com\sergiosgc\form\Serializer_Bootstrap_Input' => true,
        '\com\sergiosgc\form\Serializer_Bootstrap_Email' => true,
    );
    protected static function registerInputClass($class) {/*{{{*/
        self::$inputClasses[$class] = true;
    }/*}}}*/
    protected static function deregisterInputClass($class) {/*{{{*/
        unset(self::$inputClasses[$class]);
    }/*}}}*/
    protected static function getInputClasses() {/*{{{*/
        return array_keys(self::$inputClasses);
    }/*}}}*/

    public function setLayout($layout) {/*{{{*/
        $this->layout = $layout;
    }/*}}}*/
    public function getLayout() {/*{{{*/
        return $this->layout;
    }/*}}}*/
    public function serialize(Form $form) {/*{{{*/
        switch ($this->layout) {
        case 'horizontal':
        case 'horizontal_1':
        case 'horizontal_2':
        case 'horizontal_3':
        case 'horizontal_4':
        case 'horizontal_5':
        case 'horizontal_6':
        case 'horizontal_7':
        case 'horizontal_8':
        case 'horizontal_9':
        case 'horizontal_10':
        case 'horizontal_11':
            $markup = <<<EOS
<form class="form-horizontal" action="%s" method="%s">
%s
%s
</form>
EOS;
            break;
        case 'inline':
            $markup = <<<EOS
<form class="form-inline" action="%s" method="%s">
%s
%s
</form>
EOS;
            break;
        default:
            $markup = <<<EOS
<form action="%s" method="%s">
%s
%s
</form>
EOS;
        }
        $form->sortFields();
        printf($markup, $form->getAction(), $form->getMethod(), $this->serializeFields($form), $this->serializeSubmitTargets($form));
    }/*}}}*/
    protected function serializeFields($form) {/*{{{*/
        $fieldSets = array( 'default' => array() );
        $result = '';
        foreach ($form as $field) {
            /*#
             * Allow for a field to be assigned a fieldset
             *
             * The bootstrap form serializer allows fields to be 
             * organized into fieldsets. By default, there is only one
             * fieldset, named 'default'. Should you wish to assign a 
             * different fieldset for a given field, just capture this hook
             * and return the name of the fieldset
             *
             * @param string The currently assigned fieldset
             * @param com.sergiosgc.form.Field The field being assigned
             * @param com.sergiosgc.form.Form The form being serialized
             * @return  string The fieldset name for the field
             */
            $fieldSet = zm_fire('com.sergiosgc.form.bootstrap.fieldset', 'default', $field, $form);
            if (!isset($fieldSets[$fieldSet])) $fieldSets[$fieldSet] = array();
            $fieldSets[$fieldSet][] = $field;
        }
        foreach ($fieldSets as $name => $fields) $result .= $this->serializeFieldSet($name, $fields, $form);
        return $result;
    }/*}}}*/
    protected function serializeSubmitTargets($form) { /*{{{*/
        $serializer = new Serializer_Bootstrap_Submit();
        $result = '';
        foreach ($form->getSubmitTargets() as $key => $label) {
            $result .= $serializer->serialize($key, $label, $form, $this, true);
        }
        return $result;
    }/*}}}*/
    protected function serializeFieldSet($name, $fields, $form) {/*{{{*/
        $result = '';
        if ($this->layout != 'inline') $result = '<fieldset>';
        foreach ($fields as $field) $result .= $this->serializeField($field, $form);
        if ($this->layout != 'inline') $result .= '</fieldset>';
        return $result;
    }/*}}}*/
    protected function serializeField($field, $form) {/*{{{*/
        $input = $this->selectInputForField($field, $form);
        return $input->serialize($field, $form, $this);
    }/*}}}*/
    protected function selectInputForField($field, $form) {/*{{{*/
        $bestMatch = array(
            'score' => 0,
            'class' => null);
        foreach (self::getInputClasses() as $inputClass) {
            $score = $inputClass::getFitnessForField($field, $form);
            /*#
             * Allow for the fitness score of an input on a field to be mangled
             *
             * Bootstrap selects the best input class for a given field by
             * having each input class assign a fitness score for displaying the
             * field, and then selecting the most fit. This hook allows the fitness 
             * score to be mangled.
             *
             * Bar special needs, Inputs should report as their fitness the 
             * number of behaviours of the field that they know how to handle, plus 1. 
             * For example, if a field has both a multiline behaviour and an
             * HTML behaviour, then it will receive a fitness score of 2
             * by the textarea input, stating it can handle the multiline behaviour,
             * and a fitness score of 3 by the HTMLEditor input, stating that it
             * can handle both the multiline and the HTML behaviours.
             *
             * Bootstrap_Input will always return a fitness of 1, so it is
             * the last resort input.
             *
             * @param int The score reported by the input class
             * @param string The input class name
             * @param com.sergiosgc.form.field The field being targeted
             * @param com.sergiosgc.form.Form The form owning the field
             * @param int The fitness score
             */
            $score = zm_fire('com.sergiosgc.form.bootstrap.getfitness', $score, $inputClass, $field, $form);
            if ($score > $bestMatch['score']) {
                $bestMatch['score'] = $score;
                $bestMatch['class'] = $inputClass;
            }
            if ($score == $bestMatch['score']) {
                /*#
                 * A tie has ocurred when evaluating the fitness of inputs for a field. Allow it to be decided
                 *
                 * Whenever two inputs tie in the fitness score for a given input, 
                 * the default behaviour is to use the first one found. This hook allows
                 * changing this behaviour. Note that nothing mandates that the result
                 * be one of the two contending classes.
                 *
                 * @param string The previous input class name
                 * @param string The contender class name
                 * @param com.sergiosgc.form.field The field being targeted
                 * @param com.sergiosgc.form.Form The form owning the field
                 * @param string The selected class
                 */
                $winnerClass = zm_fire('com.sergiosgc.form.bootstrap.getfitness.tie', $bestMatch['class'], $inputClass, $field, $form);
                $bestMatch['class'] = $winnerClass;
            }
        }
        if (!is_object(self::$inputClasses[$bestMatch['class']])) self::$inputClasses[$bestMatch['class']] = new $bestMatch['class']();
        return self::$inputClasses[$bestMatch['class']];
    }/*}}}*/
}
