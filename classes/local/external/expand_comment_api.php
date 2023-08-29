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
 * Expand comment services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\local\external;

use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;

defined('MOODLE_INTERNAL') || die();

if (class_exists('core_external\external_api')) {
    require_once($CFG->dirroot . '/lib/external/classes/external_api.php');
    require_once($CFG->dirroot . '/lib/external/classes/external_function_parameters.php');
    require_once($CFG->dirroot . '/lib/external/classes/external_single_structure.php');
    require_once($CFG->dirroot . '/lib/external/classes/external_multiple_structure.php');
    require_once($CFG->dirroot . '/lib/external/classes/external_value.php');
} else {
    require_once($CFG->libdir . '/externallib.php');
}

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');

/**
 * Expand comment services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expand_comment_api extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function expand_comment_parameters() {
        return new external_function_parameters([
                'studentquizquestionid' => new external_value(PARAM_INT, 'SQQ ID'),
                'cmid' => new external_value(PARAM_INT, 'Cm ID'),
                'commentid' => new external_value(PARAM_INT, 'Comment ID'),
                'type' => new external_value(PARAM_INT, 'Comment type', VALUE_DEFAULT, utils::COMMENT_TYPE_PUBLIC)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function expand_comment_returns() {
        $replystructure = utils::get_comment_area_webservice_comment_reply_structure();
        $repliesstructure = $replystructure;
        $repliesstructure['replies'] = new external_multiple_structure(
                new external_single_structure($replystructure), 'List of replies belong to first level comment'
        );
        return new external_single_structure($repliesstructure);
    }

    /**
     * Get posts belong to diccussion.
     *
     * @param int $studentquizquestionid - SQQ ID
     * @param int $cmid - CM ID
     * @param int $commentid - Comment ID
     * @param int $type - Comment type.
     * @return mixed
     */
    public static function expand_comment($studentquizquestionid, $cmid, $commentid, $type) {

        $params = self::validate_parameters(self::expand_comment_parameters(), [
                'studentquizquestionid' => $studentquizquestionid,
                'cmid' => $cmid,
                'commentid' => $commentid,
                'type' => $type
        ]);

        $studentquizquestion = utils::get_data_for_comment_area($params['studentquizquestionid'], $params['cmid']);
        $context = $studentquizquestion->get_context();
        self::validate_context($context);
        $commentarea = new container($studentquizquestion, null, '', $type);

        $comment = $commentarea->query_comment_by_id($params['commentid']);

        if (!$comment) {
            throw new \moodle_exception(\get_string('invalidcomment', 'studentquiz'), 'studentquiz');
        }

        $data = $comment->convert_to_object();

        $data->replies = [];

        foreach ($comment->get_replies() as $reply) {
            $data->replies[] = $reply->convert_to_object();
        }

        return $data;
    }
}
