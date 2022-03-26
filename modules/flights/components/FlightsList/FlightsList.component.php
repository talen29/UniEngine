<?php

namespace UniEngine\Engine\Modules\Flights\Components\FlightsList;

/**
 * @param object $params
 * @param string $params['fleetId']
 * @param number $params['eventTimestamp']
 */
function _createFleetSortKey($params) {
    return implode('', [
        $params['eventTimestamp'],
        str_pad($params['fleetId'], 20, '0', STR_PAD_LEFT)
    ]);
}

//  Arguments
//      - $props (Object)
//          - flights
//          - fleetOwnerId (String)
//          - isPhalanxView (Boolean)
//          - currentTimestamp (Number)
//
//  Returns: Object
//      - componentHTML (String)
//
function render ($props) {
    global $_EnginePath;

    $tplParams = [
        'flightsList' => null,
    ];

    $flights = $props['flights'];
    $fleetOwnerId = $props['fleetOwnerId'];
    $isPhalanxView = $props['isPhalanxView'];
    $currentTimestamp = $props['currentTimestamp'];

    if ($flights->num_rows === 0) {
        return [
            'componentHTML' => ''
        ];
    }

    include_once("{$_EnginePath}includes/functions/BuildFleetEventTable.php");

    $entryIdx = 0;
    $flightsListEntries = [];

    while ($flight = $flights->fetch_assoc()) {
        $entryIdx += 1;

        $fleetId = $flight['fleet_id'];
        $fleetStartTime = $flight['fleet_start_time'];
        $fleetHoldTime = $flight['fleet_end_stay'];
        $fleetEndTime = $flight['fleet_end_time'];

        $isOwnersFleet = $flight['fleet_owner'] == $fleetOwnerId;
        $isPartOfACSFlight = !empty($flight['fleets_id']);

        if ($isPhalanxView) {
            $flight['fleet_resource_metal'] = 0;
            $flight['fleet_resource_crystal'] = 0;
            $flight['fleet_resource_deuterium'] = 0;
        }

        if ($isPartOfACSFlight) {
            $flight['fleet_mission'] = 2;
        }

        if ($fleetStartTime > $currentTimestamp) {
            $entryKey = _createFleetSortKey([
                'fleetId' => $fleetId,
                'eventTimestamp' => $fleetStartTime
            ]);
            $Label = 'fs';

            $flightsListEntries[$entryKey] = BuildFleetEventTable(
                $flight,
                0,
                $isOwnersFleet,
                $Label,
                $entryIdx,
                $isPhalanxView
            );
        }

        // If the mission will eventually return to the origin place (not "stay")
        if ($flight['fleet_mission'] == 4) {
            continue;
        }

        if ($fleetHoldTime > $currentTimestamp) {
            $entryKey = _createFleetSortKey([
                'fleetId' => $fleetId,
                'eventTimestamp' => $fleetHoldTime
            ]);
            $Label = 'ft';

            $flightsListEntries[$entryKey] = BuildFleetEventTable(
                $flight,
                1,
                $isOwnersFleet,
                $Label,
                $entryIdx,
                $isPhalanxView
            );
        }
        if (
            $isOwnersFleet &&
            $fleetEndTime > $currentTimestamp
        ) {
            $entryKey = _createFleetSortKey([
                'fleetId' => $fleetId,
                'eventTimestamp' => $fleetEndTime
            ]);
            $Label = 'fe';

            $flightsListEntries[$entryKey] = BuildFleetEventTable(
                $flight,
                2,
                $isOwnersFleet,
                $Label,
                $entryIdx,
                $isPhalanxView
            );
        }
    }

    ksort($flightsListEntries, SORT_STRING);

    $tplParams['flightsList'] = implode('', $flightsListEntries);

    return [
        'componentHTML' => $tplParams['flightsList']
    ];
}

?>
