<?php

function BuildFleetEventTable($FleetRow, $Status, $Owner, $Label, $Record, $Phalanx = false)
{
    global $_Lang, $InsertJSChronoApplet_GlobalIncluded;
    static $InsertJSChronoApplet_Included = false, $Template = false, $ThisDate = false;

    if($Template === false)
    {
        global $_User;

        GlobalTemplate_AppendToAfterBody(gettemplate('_FleetTable_files'));
        $Template = gettemplate('_FleetTable_row');

        $userCustomFleetColorsStylesHTML = _getUserCustomFleetColorsStylesHTML($_User);

        if ($userCustomFleetColorsStylesHTML) {
            GlobalTemplate_AppendToAfterBody($userCustomFleetColorsStylesHTML);
        }
    }
    if($ThisDate === false)
    {
        $ThisDate = date('d/m | ');
    }

    if($InsertJSChronoApplet_GlobalIncluded !== true)
    {
        if($InsertJSChronoApplet_Included === false)
        {
            include('InsertJavaScriptChronoApplet.php');
            $InsertJSChronoApplet_Included = true;
        }
    }

    if($Owner == true)
    {
        $FleetPrefix = 'own';
    }
    else
    {
        $FleetPrefix = '';
    }

    $MissionType    = $FleetRow['fleet_mission'];
    $FleetContent    = CreateFleetPopupedFleetLink($FleetRow, (($Owner === true) ? $_Lang['ov_fleet'] : $_Lang['ov_fleet2']));
    $FleetMission    = $_Lang['type_mission'][$MissionType];

    $StartType        = $FleetRow['fleet_start_type'];
    $TargetType        = $FleetRow['fleet_end_type'];

    if($Status != 2)
    {
        if($StartType == 1)
        {
            $StartID = $_Lang['ov_planet_to'];
        }
        else if($StartType == 3)
        {
            $StartID = $_Lang['ov_moon_to'];
        }
        $StartID .= $FleetRow['start_name'].' ';
        $StartID .= GetStartAdressLink($FleetRow, 'white', $Phalanx);

        if($MissionType != 15)
        {
            if($TargetType == 1)
            {
                $TargetID = $_Lang['ov_planet_to_target'];
            }
            else if($TargetType == 2)
            {
                $TargetID = $_Lang['ov_debris_to_target'];
            }
            else if($TargetType == 3)
            {
                $TargetID = $_Lang['ov_moon_to_target'];
            }
        }
        else
        {
            $TargetID = $_Lang['ov_explo_to_target'];
        }
        $TargetID .= $FleetRow['end_name'].' ';
        $TargetID .= GetTargetAdressLink($FleetRow, 'white', $Phalanx);
    }
    else
    {
        if($StartType == 1)
        {
            $StartID = $_Lang['ov_back_planet'];
        }
        else if($StartType == 3)
        {
            $StartID = $_Lang['ov_back_moon'];
        }
        $StartID .= $FleetRow['start_name'].' ';
        $StartID .= GetStartAdressLink($FleetRow, 'white', $Phalanx);

        if($MissionType != 15)
        {
            if($TargetType == 1)
            {
                $TargetID = $_Lang['ov_planet_from'];
            }
            else if($TargetType == 2)
            {
                $TargetID = $_Lang['ov_debris_from'];
            }
            else if($TargetType == 3)
            {
                $TargetID = $_Lang['ov_moon_from'];
            }
        }
        else
        {
            $TargetID = $_Lang['ov_explo_from'];
        }
        $TargetID .= $FleetRow['end_name'].' ';
        $TargetID .= GetTargetAdressLink($FleetRow, 'white', $Phalanx);
    }

    if($Owner == true)
    {
        if($Phalanx === false)
        {
            $EventString = $_Lang['ov_une'];
        }
        else
        {
            $EventString = $_Lang['ov_une_phalanx'];
        }
        $EventString .= $FleetContent;
    }
    else
    {
        $EventString = $_Lang['ov_une_hostile'];
        $EventString .= $FleetContent;
        $EventString .= $_Lang['ov_hostile'];
        $EventString .= BuildHostileFleetPlayerLink($FleetRow , $Phalanx);
    }

    if($Status == 0)
    {
        $Time = $FleetRow['fleet_start_time'];
        $Rest = $Time - time();
        $EventString .= $_Lang['ov_vennant'];
        $EventString .= $StartID;
        $EventString .= $_Lang['ov_atteint'];
        $EventString .= $TargetID;
        $EventString .= $_Lang['ov_mission'];
    }
    else if($Status == 1)
    {
        $Time = $FleetRow['fleet_end_stay'];
        $Rest = $Time - time();
        $EventString .= $_Lang['ov_vennant'];
        $EventString .= $StartID;
        if($FleetRow['fleet_mission'] == 5)
        {
            $EventString .= $_Lang['ov_onorbit_stay'];
        }
        else
        {
            $EventString .= $_Lang['ov_explo_stay'];
        }
        $EventString .= $TargetID;
        $EventString .= $_Lang['ov_explo_mission'];
    }
    else if($Status == 2)
    {
        $Time = $FleetRow['fleet_end_time'];
        $Rest = $Time - time();
        $EventString .= $_Lang['ov_rentrant'];
        $EventString .= $TargetID;
        $EventString .= $StartID;
        $EventString .= $_Lang['ov_mission'];
    }
    $EventString .= $FleetMission;

    $bloc['fleet_status']        = _getFleetStatus($Status);
    $bloc['fleet_prefix']        = $FleetPrefix;
    $bloc['fleet_style']        = _getMissionStyle($MissionType);
    $bloc['fleet_order']        = $Label.$Record;
    $bloc['fleet_countdown']    = pretty_time($Rest, true);
    $bloc['fleet_time']            = str_replace($ThisDate, '', date('d/m | H:i:s', $Time));
    $bloc['fleet_descr']        = $EventString;
    GlobalTemplate_AppendToAfterBody(InsertJavaScriptChronoApplet($Label, $Record, $Rest));

    return parsetemplate($Template, $bloc);
}

function _getFleetStatus($statusID) {
    $fleetStatuses = [
        0 => 'flight',
        1 => 'holding',
        2 => 'return'
    ];

    return $fleetStatuses[$statusID];
}

function _getMissionStyle($missionID) {
    $missionStyles = [
        1 => 'attack',
        2 => 'federation',
        3 => 'transport',
        4 => 'deploy',
        5 => 'hold',
        6 => 'espionage',
        7 => 'colony',
        8 => 'harvest',
        9 => 'destroy',
        10 => 'missile',
    ];

    return $missionStyles[$missionID];
}

function _getUserCustomFleetColorsStylesHTML(&$user) {
    if (empty($user['settings_FleetColors'])) {
        return null;
    }

    $fleetColorsSettings = json_decode($user['settings_FleetColors'], true);

    $stylesData = [];

    foreach ($fleetColorsSettings as $fleetType => $perMissionColors) {
        $isOwnFleet = ($fleetType !== 'nonown');
        $isOwnComeback = ($fleetType === 'owncb');
        $missionType = (
            $isOwnComeback ?
            "flight" :
            "return"
        );

        foreach ($perMissionColors as $missionID => $missionColor) {
            if (empty($missionColor)) {
                continue;
            }

            $stylesData[] = [
                'MissionType' => $missionType,
                'MissionName' => (
                    ($isOwnFleet ? 'own' : '') .
                    _getMissionStyle($missionID)
                ),
                'MissionColor' => $missionColor
            ];

            if (
                $missionID == 5 &&
                !$isOwnComeback
            ) {
                $stylesData[] = [
                    'MissionType' => 'holding',
                    'MissionName' => (
                        ($isOwnFleet ? 'own' : '') .
                        _getMissionStyle($missionID)
                    ),
                    'MissionColor' => $missionColor
                ];
            }
        }
    }

    if (empty($stylesData)) {
        return null;
    }

    $tplColorRow = gettemplate('_FleetTable_fleetColors_row');
    $tplColorsStyles = gettemplate('_FleetTable_fleetColors');

    $missionStyles = array_map(
        function ($styleData) use ($tplColorRow) {
            return parsetemplate($tplColorRow, $styleData);
        },
        $stylesData
    );
    $missionStyles = implode(' ', $missionStyles);

    return parsetemplate($tplColorsStyles, [ 'InsertStyles' => $missionStyles ]);
}

?>
