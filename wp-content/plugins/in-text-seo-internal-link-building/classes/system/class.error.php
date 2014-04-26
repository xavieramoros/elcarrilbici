<?php

class Error {

    private $sError;
    private $bDebug = true;

    public function Error() {
        
    }

    public function Debug($sStr) {
        echo $sStr . "\n";
    }

}

?>