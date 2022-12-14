<?php

namespace UniEngine\Engine\Includes\Helpers\World\Checks;

/**
 * @param object $params
 * @param number $params['originPosition']
 * @param number $params['targetPosition']
 * @param number $params['range']
 */
function isTargetInRange($params) {
    $originPosition = $params['originPosition'];
    $targetPosition = $params['targetPosition'];
    $range = $params['range'];

    $positionsDistance = abs($originPosition - $targetPosition);

    return ($range >= $positionsDistance);
}

?>
