<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class Behaviour_File implements Behaviour {
    public function __construct($mime = null) {
        $this->mimeType = $mime;
    }
    public function getAcceptedMimeType() {
        return $this->mimeType;
    }
}
