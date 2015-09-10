<?php

/**
 * The post diff hook to push the diff to GitHub for CI and patching purposes.
 */
final class PostDiffHook extends BaseHook {

    const OUT_PREFIX = 'DIFF';

    public function doHook(ArcanistWorkflow $workflow) {
        $diff_obj = $this->getDiffObj($workflow);

        $revision_id = HookUtils::getStringValueFromObj(self::PH_REVISIONID,
                                                        $diff_obj);
        $topic_branch = HookUtils::getStringValueFromObj(self::PH_BRANCH,
                                                         $diff_obj);
        if (!$topic_branch) {
            $topic_branch = 'HEAD:refs/heads/';
        } else {
            $topic_branch = "$topic_branch:";
        }

        $revision_dict = $this->getRevisionObj($workflow, $revision_id);
        if ($revision_dict) {
            if ($revision_id && $topic_branch) {
                $remote_branch_name =
                    HookUtils::createRemoteBranchName($revision_id,
                                                      $topic_branch);
                $this->pushBranchToRemote($topic_branch, $remote_branch_name);
            } else {
                $this->writeErr('Could not determine branch name.');
            }
        }
    }

    private function getRevisionObj(ArcanistWorkflow $workflow, $revision_id) {
        $conduit = $workflow->getConduit();
        $query = array('ids' => array($revision_id));

        $revision_dict_array =
            $conduit->callMethodSynchronous('differential.query', $query);

        // look for index 0, since there should only be one result when
        // looking up by id
        $revision_dict = null;
        if (array_key_exists(0, $revision_dict_array)) {
            $revision_dict = $revision_dict_array[0];
        } else {
            $this->writeErr('Did not find revision from Phabricator');

            $error_message =
                HookUtils::getStringValueFromObj('error_message',
                                                 $revision_dict_array);

            if ($error_message) {
                $this->writeErr($error_message);
            }
        }

        return $revision_dict;
    }

    private function getDiffObj(ArcanistWorkflow $workflow) {
        $diff_id = $workflow->getDiffID();

        // The diff information is not in the workflow object, so we need
        // to request it from Phabricator via the diff_id. One "differential"
        // can have many "diffs", e.g. if you amend a commit or have
        // multipe ones for the same differential. The "revision"
        // refers to the whole differential.
        // set up our query
        $conduit = $workflow->getConduit();
        $query = array('ids' => array($diff_id));

        // This gives a key/value pair of results, e.g.:
        // 11 => { ... the diff object ... }
        // where "11" is the diff_id
        $diff_query_result_arr =
            $conduit->callMethodSynchronous('differential.querydiffs', $query);

        $diff_obj = null;
        if (array_key_exists($diff_id, $diff_query_result_arr)) {
            $diff_obj = $diff_query_result_arr[$diff_id];
        } else {
            $this->writeErr(pht(
                'Did not find diff with id %s from Phabricator',
                $diff_id));

            $error_message =
                HookUtils::getStringValueFromObj('error_message',
                                                 $diff_query_result_arr);

            if ($error_message) {
                $this->writeErr($error_message);
            }
        }

        return $diff_obj;
    }

    private function pushBranchToRemote($topic_branch, $remote_branch_name) {
        // Using force here because we don't really care what was there
        // before... we just want the new changes to get CI'd.
        $shell_command = "git push origin '$topic_branch$remote_branch_name'".
                         " --force";
        $git_command = escapeshellcmd($shell_command);

        $this->writeOut(pht(
            "Pushing to GitHub with this command:\n    %s\n",
            $git_command));

        $exit_code = 0;
        passthru($git_command, $exit_code);
        if ($exit_code) {
            $this->writeErr('The push to GitHub failed.');
        }
    }

    protected function getOutPrefix() {
        return self::OUT_PREFIX;
    }
}
?>
