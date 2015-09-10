<?php

final class PostLandHook extends BaseHook {

    const OUT_PREFIX = 'LAND';

    public function doHook(ArcanistWorkflow $workflow) {
        $dict = $workflow->getRevisionDict();

        if ($dict) {
            // Here we actually have a differential object, aka a revision.
            $revision_id = HookUtils::getStringValueFromObj(self::PH_ID, $dict);
            $topic_branch =
                HookUtils::getStringValueFromObj(self::PH_BRANCH, $dict);

            if ($revision_id && $topic_branch) {
                $remote_branch_name =
                    HookUtils::createRemoteBranchName($revision_id,
                                                      $topic_branch);

                $this->deleteRemoteBranch($remote_branch_name);
            }
        }
    }

    public function deleteRemoteBranch($remote_branch_name) {
        if (!$remote_branch_name) {
            $this->writeErr('Cannot delete a remote branch with no name');
            return;
        }
        $git_command = escapeshellcmd(pht(
            "git push origin --delete '%s'", $remote_branch_name));

        $this->writeOut(pht(
            "Removing branch %s after landing with this command:\n    %s\n",
            $remote_branch_name, $git_command));

        $exit_code = 0;
        passthru($git_command, $exit_code);
        if ($exit_code) {
            $this->writeErr(pht(
                'Failed to delete temporary branch %s',
                $remote_branch_name));
        }
    }

    protected function getOutPrefix() {
        return self::OUT_PREFIX;
    }
}
?>
