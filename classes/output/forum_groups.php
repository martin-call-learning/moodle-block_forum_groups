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
defined('MOODLE_INTERNAL') || die();

use context_course;
use context_helper;
use context_module;
use core_course\external\course_summary_exporter;
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
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($coursemodule) {
        $this->courseid = $coursemodule->course;
        $this->forumid = $coursemodule->instance;
        $this->cm = $coursemodule;
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

        $context->groups  = [];
        $groups = groups_get_all_groups($this->courseid, 0, 0, 'g.*', true);
        foreach($groups as $group) {

            $messagecount = $this->get_forum_message_count($group->id, $this->forumid);
            $forumlink  = new moodle_url('/mod/forum/view.php', array(
                'id' => $this->cm->id,
                'group' => $group->id
                ));
            $context->groups[]  = [
                'name' => $group->name,
                'memberscount' => count($group->members),
                'link' => $forumlink->out(false),
                'messagecount' => $messagecount
            ];
        }

        return $context;
    }

    protected function get_forum_message_count($groupid, $forumid) {
        global $DB;
        return $DB->count_records_sql('SELECT COUNT(*) FROM {forum_discussions} d 
            LEFT JOIN {forum_posts} p ON p.discussion = d.id
            WHERE d.groupid = :groupid AND d.forum = :forumid
        ', array('forumid'=>$forumid, 'groupid'=>$groupid));
    }
}