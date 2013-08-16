<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

class Serializer_Bootstrap_Input {
    protected $type = 'text';
    public static function getFitnessForField($field, $form) {
        return 1;
    }
    public function serialize($field, $form, $bootstrap) {/*{{{*/
        switch ($bootstrap->getLayout()) {
        case 'inline':
            return $this->serializeInline($field, $form, $bootstrap);
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
        case 'horizontal_12':
            return $this->serializeHorizontal($field, $form, $bootstrap);
        default:
            return $this->serializeDefault($field, $form, $bootstrap);
        }
        return $field->getLabel();
    }/*}}}*/
    public function serializeDefault($field, $form, $bootstrap) {/*{{{*/
        $divClass = '';
        if ($field->getErrorText() != '') $divClass .= ' has-error';
        $divClass = trim($divClass);
        if ($divClass != '') $divClass = ' class="' . $divClass . '"';

        $inputClass = '';
        if ($field->getErrorText() != '') $inputClass .= ' input-with-feedback';
        $inputClass = trim($inputClass);
        if ($inputClass != '') $inputClass = ' class="' . $inputClass . '"';

        $helpText ='';
        if ($field->getHelpText() != '') $helpText = sprintf('<span class="help-block">%s</span>', $field->getHelpText());
        $errorText ='';
        if ($field->getErrorText() != '') $errorText = sprintf('<div class="alert alert-danger">%s</div>', $field->getErrorText());

        $result = sprintf(<<<EOS
<div%s>
 <label for="%s" class="control-label">%s</label>
 <input type="%s" name="%s" value="%s" placeholder="%s"%s>
 %s
</div>
EOS
            , $divClass
            , $field->getName()
            , $field->getLabel()
            , $this->type
            , $field->getName()
            , strtr($field->getValue(), array( '&' => '&amp;', '"' => '&quot;' ))
            , $field->getPlaceholderText()
            , $inputClass
            , $errorText == '' ? $helpText : $errorText);
        return $result;
    }/*}}}*/
    public function serializeInline($field, $form, $bootstrap) {/*{{{*/
        $divClass = '';
        if ($field->getErrorText() != '') $divClass .= ' has-error';
        $divClass = trim($divClass);
        if ($divClass != '') $divClass = ' class="' . $divClass . '"';

        $inputClass = '';
        if ($field->getErrorText() != '') $inputClass .= ' input-with-feedback';
        $inputClass = trim($inputClass);
        if ($inputClass != '') $inputClass = ' class="' . $inputClass . '"';

        $helpText ='';
        if ($field->getHelpText() != '') $helpText = sprintf('<span class="help-block">%s</span>', $field->getHelpText());
        $errorText ='';
        if ($field->getErrorText() != '') $errorText = sprintf('<div class="alert alert-danger">%s</div>', $field->getErrorText());

        $result = sprintf(<<<EOS
<input type="%s" name="%s" value="%s" placeholder="%s">
EOS
            , $this->type
            , $field->getName()
            , strtr($field->getValue(), array( '&' => '&amp;', '"' => '&quot;' ))
            , $field->getLabel());
        return $result;
    }/*}}}*/
    public function serializeHorizontal($field, $form, $bootstrap) {/*{{{*/
        $labelWidth = explode('_', $bootstrap->getLayout());
        $labelWidth = count($labelWidth) == 1 ? 3 : (int) $labelWidth[1];
        $fieldWidth = 12 - $labelWidth;

        $divClass = ' row';
        if ($field->getErrorText() != '') $divClass .= ' has-error';
        $divClass = trim($divClass);
        if ($divClass != '') $divClass = ' class="' . $divClass . '"';

        $inputClass = '';
        if ($field->getErrorText() != '') $inputClass .= ' input-with-feedback';
        $inputClass = trim($inputClass);
        if ($inputClass != '') $inputClass = ' class="' . $inputClass . '"';

        $helpText ='';
        if ($field->getHelpText() != '') $helpText = sprintf('<span class="help-block">%s</span>', $field->getHelpText());
        $errorText ='';
        if ($field->getErrorText() != '') $errorText = sprintf('<div class="alert alert-danger">%s</div>', $field->getErrorText());

        $result = sprintf(<<<EOS
<div%s>
 <label for="%s" class="col-lg-%d control-label">%s</label>
 <div class="col-lg-%d">
  <input type="%s" name="%s" value="%s" placeholder="%s"%s>
 </div>
 <div class="col-lg-%d col-offset-%d">
  %s
 </div>
</div>
EOS
            , $divClass
            , $field->getName()
            , $labelWidth
            , $field->getLabel()
            , $fieldWidth
            , $this->type
            , $field->getName()
            , strtr($field->getValue(), array( '&' => '&amp;', '"' => '&quot;' ))
            , $field->getPlaceholderText()
            , $inputClass
            , $fieldWidth
            , $labelWidth
            , $errorText == '' ? $helpText : $errorText);
        return $result;
    }/*}}}*/
}

