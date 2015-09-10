<?php

/**
 * Static utility helpers
 */
final class HookUtils extends Phobject {
    const PH_TITLE = 'title';
    const PH_SUMMARY = 'summary';
    const PH_TESTPLAN = 'testPlan';

    /**
     * Create the remote branch name in a consistent way
     */
    public static function createRemoteBranchName($revision_id, $topic_branch) {
        return pht('ND_D%s', $revision_id);
    }

    /**
     * Safe way to get string value out of obj.
     */
    public static function getStringValueFromObj($field, $obj) {
        $ret = null;
        if (array_key_exists($field, $obj)) {
            $ret = $obj[$field];
        }
        return is_string($ret) ? $ret : null;
    }
}
?>
