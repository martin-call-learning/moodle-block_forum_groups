<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block forum_groups is defined here.
 *
 * @package     block_forum_groups
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_forum_groups\output;

use context_course;
use context_helper;
use context_module;
use core_course\external\course_summary_exporter;
use mod_forum\local\container;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use user_picture;

/**
 * Block forum_groups is defined here.
 *
 * @package     block_forum_groups
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_groups implements renderable, templatable {
    /**
     * @var object $config
     */
    protected $config = null;

    /**
     * @var $forumid
     */
    protected $forumid = null;

    /**
     * @var $courseid
     */
    protected $courseid = null;

    /**
     * @var \cm_info|null $cm
     */
    protected $cm = null;

    /**
     * forum_groups constructor.
     * Retrieve matching forum posts sorted in reverse order
     *
     * @param \cm_info $coursemodule
     * @param object $config
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($coursemodule, $config) {
        $this->courseid = $coursemodule->course;
        $this->forumid = $coursemodule->instance;
        $this->cm = $coursemodule;
        $this->config = $config;
    }

    /**
     * Export featured course data
     *
     * @param renderer_base $renderer
     * @return object
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $renderer) {
        $context = new \stdClass();

        $context->groups = [];
        $groups = groups_get_all_groups($this->courseid, 0, 0, 'g.*', true);
        foreach ($groups as $group) {
            $messagecount = static::get_forum_message_count($group->id, $this->forumid);
            $forumlink = new moodle_url('/mod/forum/view.php', array(
                'id' => $this->cm->id,
                'group' => $group->id
            ));
            $ismember = groups_is_member($group->id);
            if (!empty($this->config->showall) || $ismember) {
                $context->groups[] = [
                    'name' => $group->name,
                    'memberscount' => count($group->members),
                    'link' => $forumlink->out(false),
                    'messagecount' => $messagecount,
                    'ismember' => $ismember
                ];
            }
        }
        usort($context->groups, function($val1, $val2) {
            return $val1['ismember'] < $val2['ismember'];
        });
        return $context;
    }

    /**
     * Get count for messages
     *
     * @param int $groupid
     * @param int $forumid
     * @return int|void
     */
    public static function get_forum_message_count($groupid, $forumid) {
        global $USER;
        $currentgroupid = $groupid;
        $vaultfactory = container::get_vault_factory();
        $forumvault = $vaultfactory->get_forum_vault();
        $forum = $forumvault->get_from_id($forumid);

        $alldiscussions = mod_forum_get_discussion_summaries($forum, $USER, $currentgroupid, 0);

        $alldiscussions = array_filter($alldiscussions, function($disc) use ($currentgroupid) {
            return $disc->get_discussion()->get_group_id() == $currentgroupid;
        });
        return count($alldiscussions);
    }
}
