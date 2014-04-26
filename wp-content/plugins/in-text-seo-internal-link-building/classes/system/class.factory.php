<?php
/**
 * class Factory
 *
 * @AppStylus
 * @Frederic Montes
 * User friendly instantiate remote/local classes  
 */
class Factory {
    /*
     * Constructor: ReflectionClass for bypassing arguments as is
     */

    function Factory() {
        //Something to do?
    }

    function create() {
        // TODO Get the local/remote class

        $args = func_get_args();
        $iArgs = count($args);
        if ($iArgs == 0) {
            Error::Debug('Class must be defined');
            return null;
        } else {
            $class = array_shift($args);
        }

        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);
            $object = $reflection->newInstanceArgs($args);
            return $object;
        } else {
            Error::Debug('The class [$class] is not defined');
            return null;
        }
    }
}
?>