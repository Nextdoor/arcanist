<?php

abstract class BaseHook extends Phobject {
    const PH_ID = 'id';
    const PH_REVISIONID = 'revisionID';
    const PH_BRANCH = 'branch';

    protected $console;

    public function __construct() {
        $this->console = PhutilConsole::getConsole();
    }

    abstract public function doHook(ArcanistWorkflow $workflow);

    protected function writeOut($str) {
        $this->console->writeOut(pht(
            "%s: %s\n", $this->getOutPrefix(), $str));
    }

    protected function writeErr($str) {
        $this->console->writeOut(pht(
            "%s: !!! %s\n", $this->getOutPrefix(), $str));
    }

    abstract protected function getOutPrefix();

}
?>
