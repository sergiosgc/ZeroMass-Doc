<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

class Serializer_Bootstrap_Submit {
    protected $type = 'text';
    public function serialize($name, $label, $form, $bootstrap, $primary) {/*{{{*/
        switch ($bootstrap->getLayout()) {
        case 'inline':
            return $this->serializeInline($name, $label, $form, $bootstrap, $primary);
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
            return $this->serializeHorizontal($name, $label, $form, $bootstrap, $primary);
        default:
            return $this->serializeDefault($name, $label, $form, $bootstrap, $primary);
        }
        return $label;
    }/*}}}*/
    public function serializeDefault($name, $label, $form, $bootstrap, $primary) {/*{{{*/
        $result = sprintf(<<<EOS
<button type="submit" class="btn%s" type="submit" value="%s">%s</button>
EOS
        , $primary ? ' btn-primary' : '',
            $name,
            $label);
        return $result;
    }/*}}}*/
    public function serializeInline($name, $label, $form, $bootstrap, $primary) {/*{{{*/
        return $this->serializeDefault($name, $label, $form, $bootstrap, $primary);
    }/*}}}*/
    public function serializeHorizontal($name, $label, $form, $bootstrap, $primary) {/*{{{*/
        return $this->serializeDefault($name, $label, $form, $bootstrap, $primary);
    }/*}}}*/
}

