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
namespace block_forum_groups;
use advanced_testcase;
use block_forum_groups\output\forum_groups;
use stdClass;

/**
 * Class block_forum_groups
 *
 * @package     block_forum_groups
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_groups_test extends advanced_testcase {
    /**
     * Group member list
     *
     * @covers \block_forum_groups\output\forum_groups::get_forum_message_count
     */
    public function test_get_group_messages_count() {
        $this->resetAfterTest();
        // User that will create the forum.
        $user = self::getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $group = [];
        $group[0] = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group[1] = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $record = new stdClass();
        $record->course = $course->id;
        $forum = self::getDataGenerator()->create_module('forum', $record);
        // Add a few discussions.
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $user->id;
        for ($i = 0; $i < 11; $i++) {
            $record['groupid'] = $group[$i % 2]->id;
            self::getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        }
        unset($record['groupid']); // No group.
        for ($i = 0; $i < 4; $i++) {
            self::getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        }
        $this->assertEquals(6, forum_groups::get_forum_message_count($group[0]->id, $forum->id));
        $this->assertEquals(5, forum_groups::get_forum_message_count($group[1]->id, $forum->id));
        // No group.
        $this->assertEquals(4, forum_groups::get_forum_message_count(-1, $forum->id));
    }
}
