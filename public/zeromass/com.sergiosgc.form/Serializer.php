<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

interface Serializer {
    public function serialize(Form $form);
}
