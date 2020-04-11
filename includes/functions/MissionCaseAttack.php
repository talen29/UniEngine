<?php

use UniEngine\Engine\Includes\Helpers\Common\Collections;
use UniEngine\Engine\Modules\Flights;

function MissionCaseAttack($FleetRow, &$_FleetCache)
{
    global    $_EnginePath, $_Vars_Prices, $_Lang, $_Vars_GameElements, $_Vars_ElementCategories, $_GameConfig, $UserStatsData, $UserDev_Log, $IncludeCombatEngine,
            $HPQ_PlanetUpdatedFields;

    $Return = array();
    $Now = time();

    if($FleetRow['calcType'] == 1)
    {
        $TriggerTasksCheck = array();

        if($IncludeCombatEngine !== true)
        {
            // Include Combat Engine & BattleReport Creator
            include($_EnginePath.'includes/functions/CreateBattleReport.php');
            include($_EnginePath.'includes/CombatEngineAres.php');
            $IncludeCombatEngine = true;
        }

        $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_Calculated_Mission'] = true;
        $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_Calculated_Mission_Time'] = $Now;

        // Select Attacked Planet & User from $_FleetCache
        $IsAbandoned = ($FleetRow['fleet_target_owner'] > 0 ? false : true);
        $TargetPlanet = &$_FleetCache['planets'][$FleetRow['fleet_end_id']];
        $TargetUser = &$_FleetCache['users'][$FleetRow['fleet_target_owner']];
        $IsAllyFight = (($FleetRow['ally_id'] == 0 OR ($FleetRow['ally_id'] != $TargetUser['ally_id'])) ? false : true);

        // Update planet before attack begins
        $UpdateResult = HandleFullUserUpdate($TargetUser, $TargetPlanet, $_FleetCache['planets'][$TargetUser['techQueue_Planet']], $FleetRow['fleet_start_time'], true, true);
        if(!empty($UpdateResult))
        {
            foreach($UpdateResult as $PlanetID => $Value)
            {
                if($Value === true)
                {
                    $_FleetCache['updatePlanets'][$PlanetID] = true;
                }
            }
        }

        $TargetUserID = $TargetPlanet['id_owner'];
        $TargetPlanetGetName = $TargetPlanet['name'];
        $TargetPlanetID = $TargetPlanet['id'];

        if(!$IsAbandoned)
        {
            $IdleHours = floor(($FleetRow['fleet_start_time'] - $TargetUser['onlinetime']) / 3600);
            if($IdleHours > 0)
            {
                $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_End_Owner_IdleHours'] = $IdleHours;
            }
        }

        // Create data arrays for attacker and main defender
        $CurrentUserID = $FleetRow['fleet_owner'];
        $DefendersIDs[] = $TargetUser['id'];
        $AttackersIDs[] = $FleetRow['fleet_owner'];
        $AttackingFleets = array();
        $DefendingFleets = array();

        $DefendingTechs[0] = Flights\Utils\Initializers\initCombatTechnologiesMap([
            'user' => $TargetUser,
        ]);

        $DefendersData[0] = array
        (
            'id' => $TargetUser['id'],
            'username' => $TargetUser['username'],
            'techs' => Array2String($DefendingTechs[0]),
            'pos' => "{$FleetRow['fleet_end_galaxy']}:{$FleetRow['fleet_end_system']}:{$FleetRow['fleet_end_planet']}"
        );
        if(!empty($TargetUser['ally_tag']))
        {
            $DefendersData[0]['ally'] = $TargetUser['ally_tag'];
        }

        $AttackingTechs[0] = Flights\Utils\Initializers\initCombatTechnologiesMap([
            'user' => $FleetRow,
        ]);

        $AttackersData[0] = array
        (
            'id' => $FleetRow['fleet_owner'],
            'username' => $FleetRow['username'],
            'techs' => Array2String($AttackingTechs[0]),
            'pos' => "{$FleetRow['fleet_start_galaxy']}:{$FleetRow['fleet_start_system']}:{$FleetRow['fleet_start_planet']}"
        );
        if(!empty($FleetRow['ally_tag']))
        {
            $AttackersData[0]['ally'] = $FleetRow['ally_tag'];
        }

        // MoraleSystem Init
        if(MORALE_ENABLED)
        {
            if(!empty($_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]))
            {
                $FleetRow['morale_level'] = $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['level'];
                $FleetRow['morale_droptime'] = $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['droptime'];
                $FleetRow['morale_lastupdate'] = $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['lastupdate'];
            }
            Morale_ReCalculate($FleetRow, $FleetRow['fleet_start_time']);
            $AttackersData[0]['morale'] = $FleetRow['morale_level'];
            $AttackersData[0]['moralePoints'] = $FleetRow['morale_points'];

            $moraleCombatModifiers = Flights\Utils\Modifiers\calculateMoraleCombatModifiers([
                'moraleLevel' => $FleetRow['morale_level'],
            ]);

            $AttackingTechs[0] = array_merge(
                $AttackingTechs[0],
                $moraleCombatModifiers
            );

            if(!$IsAbandoned)
            {
                if(!empty($_FleetCache['MoraleCache'][$TargetUser['id']]))
                {
                    $TargetUser['morale_level'] = $_FleetCache['MoraleCache'][$TargetUser['id']]['level'];
                    $TargetUser['morale_droptime'] = $_FleetCache['MoraleCache'][$TargetUser['id']]['droptime'];
                    $TargetUser['morale_lastupdate'] = $_FleetCache['MoraleCache'][$TargetUser['id']]['lastupdate'];
                }
                Morale_ReCalculate($TargetUser, $FleetRow['fleet_start_time']);
                $DefendersData[0]['morale'] = $TargetUser['morale_level'];
                $DefendersData[0]['moralePoints'] = $TargetUser['morale_points'];

                $moraleCombatModifiers = Flights\Utils\Modifiers\calculateMoraleCombatModifiers([
                    'moraleLevel' => $TargetUser['morale_level'],
                ]);

                $DefendingTechs[0] = array_merge(
                    $DefendingTechs[0],
                    $moraleCombatModifiers
                );
            }
        }

        // Select All Defending Fleets on the Orbit from $_FleetCache
        if(!empty($_FleetCache['defFleets'][$FleetRow['fleet_end_id']]))
        {
            $i = 1;
            foreach($_FleetCache['defFleets'][$FleetRow['fleet_end_id']] as $FleetData)
            {
                if($_FleetCache['fleetRowStatus'][$FleetData['fleet_id']]['isDestroyed'] !== true)
                {
                    $DefendingFleets[$i] = String2Array($FleetData['fleet_array']);
                    $DefendingFleetID[$i] = $FleetData['fleet_id'];
                    $DefendingTechs[$i] = Flights\Utils\Initializers\initCombatTechnologiesMap([
                        'user' => $FleetData,
                    ]);
                    $DefendersData[$i] = array
                    (
                        'id' => $FleetData['fleet_owner'],
                        'username' => $FleetData['username'],
                        'techs' => Array2String($DefendingTechs[$i]),
                        'pos' => "{$FleetData['fleet_start_galaxy']}:{$FleetData['fleet_start_system']}:{$FleetData['fleet_start_planet']}"
                    );
                    if(!empty($FleetData['ally_tag']))
                    {
                        $DefendersData[$i]['ally'] = $FleetData['ally_tag'];
                    }
                    if(!in_array($FleetData['fleet_owner'], $DefendersIDs))
                    {
                        $DefendersIDs[] = $FleetData['fleet_owner'];
                    }
                    $DefendingFleetOwners[$FleetData['fleet_id']] = $FleetData['fleet_owner'];

                    if(MORALE_ENABLED)
                    {
                        if(empty($_TempCache['MoraleCache'][$FleetData['fleet_owner']]))
                        {
                            if(!empty($_FleetCache['MoraleCache'][$FleetData['fleet_owner']]))
                            {
                                $FleetData['morale_level'] = $_FleetCache['MoraleCache'][$FleetData['fleet_owner']]['level'];
                                $FleetData['morale_droptime'] = $_FleetCache['MoraleCache'][$FleetData['fleet_owner']]['droptime'];
                                $FleetData['morale_lastupdate'] = $_FleetCache['MoraleCache'][$FleetData['fleet_owner']]['lastupdate'];
                            }
                            Morale_ReCalculate($FleetData, $FleetRow['fleet_start_time']);
                            $DefendersData[$i]['morale'] = $FleetData['morale_level'];
                            $DefendersData[$i]['moralePoints'] = $FleetData['morale_points'];

                            $_TempCache['MoraleCache'][$FleetData['fleet_owner']] = array
                            (
                                'level' => $FleetData['morale_level'],
                                'points' => $FleetData['morale_points']
                            );
                        }
                        else
                        {
                            $DefendersData[$i]['morale'] = $_TempCache['MoraleCache'][$FleetData['fleet_owner']]['level'];
                            $DefendersData[$i]['moralePoints'] = $_TempCache['MoraleCache'][$FleetData['fleet_owner']]['points'];
                        }

                        $moraleCombatModifiers = Flights\Utils\Modifiers\calculateMoraleCombatModifiers([
                            'moraleLevel' => $DefendersData[$i]['morale'],
                        ]);

                        $DefendingTechs[$i] = array_merge(
                            $DefendingTechs[$i],
                            $moraleCombatModifiers
                        );
                    }

                    $i += 1;
                }
            }
        }

        foreach ($AttackersIDs as $userID) {
            if (empty($UserStatsData[$userID])) {
                $UserStatsData[$userID] = Flights\Utils\Initializers\initUserStatsMap();
            }
        }
        foreach ($DefendersIDs as $userID) {
            if (empty($UserStatsData[$userID])) {
                $UserStatsData[$userID] = Flights\Utils\Initializers\initUserStatsMap();
            }
        }

        // Create main defender fleet array
        foreach($_Vars_ElementCategories['fleet'] as $ElementID)
        {
            if($TargetPlanet[$_Vars_GameElements[$ElementID]] > 0)
            {
                $DefendingFleets[0][$ElementID] = $TargetPlanet[$_Vars_GameElements[$ElementID]];
            }
        }
        foreach($_Vars_ElementCategories['defense'] as $ElementID)
        {
            if(in_array($ElementID, $_Vars_ElementCategories['rockets']))
            {
                continue;
            }
            if($TargetPlanet[$_Vars_GameElements[$ElementID]] > 0)
            {
                $DefendingFleets[0][$ElementID] = $TargetPlanet[$_Vars_GameElements[$ElementID]];
            }
        }

        // Create attacker fleet array
        $AttackingFleets[0] = String2Array($FleetRow['fleet_array']);

        $StartTime = microtime(true);

        // Now start Combat calculations
        $Combat = Combat($AttackingFleets, $DefendingFleets, $AttackingTechs, $DefendingTechs);

        // Get the calculations time
        $EndTime = microtime(true);
        $totaltime = sprintf('%0.6f', $EndTime - $StartTime);

        $RealDebrisMetalAtk = 0;
        $RealDebrisCrystalAtk = 0;
        $RealDebrisDeuteriumAtk = 0;
        $RealDebrisMetalDef = 0;
        $RealDebrisCrystalDef = 0;
        $RealDebrisDeuteriumDef = 0;
        $TotalMoonChance = 0;
        $TotalLostMetal = 0;
        $TotalLostCrystal = 0;
        $DebrisMetalDef = 0;
        $DebrisCrystalDef = 0;

        $MoonHasBeenCreated = false;

        $RoundsData        = $Combat['rounds'];
        $Result            = $Combat['result'];
        $AtkShips        = $Combat['AttackerShips'];
        $DefShips        = $Combat['DefenderShips'];
        $AtkLost        = $Combat['AtkLose'];
        $DefLost        = $Combat['DefLose'];
        $ShotDown        = $Combat['ShotDown'];

        $QryUpdateFleets = [];
        $UserDev_UpFl = [];
        $resourcesPillage = null;

        // Parse result data - attacker fleet
        if (!empty($AtkShips[0])) {
            if ($Result === COMBAT_ATK) {
                $fleetPillageStorage = Flights\Utils\Calculations\calculatePillageStorage([
                    'fleetRow' => $FleetRow,
                    'ships' => $AtkShips[0],
                ]);

                if ($fleetPillageStorage > 0) {
                    $pillageFactor = Flights\Utils\Calculations\calculatePillageFactor([
                        'mainAttackerMoraleLevel' => $FleetRow['morale_level'],
                        'mainDefenderMoraleLevel' => $TargetUser['morale_level'],
                        'isMainDefenderIdle' => ($IdleHours >= (7 * 24)),
                        'isTargetAbandoned' => $IsAbandoned,
                        'attackerIDs' => $AttackersIDs,
                    ]);

                    $resourcesPillage = Flights\Utils\Missions\calculateEvenResourcesPillage([
                        'maxPillagePerResource' => Flights\Utils\Missions\calculateMaxPlanetPillage([
                            'planet' => $TargetPlanet,
                            'maxPillagePercentage' => $pillageFactor,
                        ]),
                        'fleetTotalStorage' => $fleetPillageStorage,
                    ]);

                    $StolenMet = $resourcesPillage['metal'];
                    $StolenCry = $resourcesPillage['crystal'];
                    $StolenDeu = $resourcesPillage['deuterium'];

                    if ($StolenMet > 0) {
                        $TriggerTasksCheck['BATTLE_COLLECT_METAL'] = $StolenMet;
                    }
                    if ($StolenCry > 0) {
                        $TriggerTasksCheck['BATTLE_COLLECT_CRYSTAL'] = $StolenCry;
                    }
                    if ($StolenDeu > 0) {
                        $TriggerTasksCheck['BATTLE_COLLECT_DEUTERIUM'] = $StolenDeu;
                    }

                    $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_End_Res_Metal'] = $StolenMet;
                    $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_End_Res_Crystal'] = $StolenCry;
                    $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_End_Res_Deuterium'] = $StolenDeu;
                }
            }

            $QryUpdateFleets[$FleetRow['fleet_id']] = Flights\Utils\Factories\createFleetUpdateEntry([
                'fleetID' => $FleetRow['fleet_id'],
                'state' => '1',
                'originalShips' => $AttackingFleets[0],
                'postCombatShips' => $AtkShips[0],
                'resourcesPillage' => $resourcesPillage,
            ]);
        } else {
            $DeleteFleet[] = $FleetRow['fleet_id'];
        }

        $UserDev_UpFl[$FleetRow['fleet_id']] = Flights\Utils\Factories\createFleetDevelopmentLogEntries([
            'originalShips' => $AttackingFleets[0],
            'postCombatShips' => $AtkShips[0],
            'resourcesPillage' => $resourcesPillage,
        ]);

        // Parse result data - Defenders
        $rebuiltDefenseSystems = [];

        $i = 1;
        if (!empty($DefendingFleets)) {
            foreach ($DefendingFleets as $User => $Ships) {
                if ($User == 0) {
                    $rebuiltDefenseSystems = Flights\Utils\Calculations\calculateUnitsRebuild([
                        'originalShips' => $DefendingFleets[0],
                        'postCombatShips' => $DefShips[0],
                        'fleetRow' => $FleetRow,
                        'targetUser' => $TargetUser,
                    ]);

                    foreach ($Ships as $ID => $Count) {
                        $Count = ($DefShips[0][$ID] + $rebuiltDefenseSystems[$ID]);

                        if ($Count == 0) {
                            $Count = '0';
                        }
                        $TargetPlanet[$_Vars_GameElements[$ID]] = $Count;
                        if ($Count < $DefendingFleets[0][$ID]) {
                            $UserDev_UpPl[] = $ID.','.($DefendingFleets[0][$ID] - $Count);
                            $_FleetCache['updatePlanets'][$TargetPlanet['id']] = true;
                            $HPQ_PlanetUpdatedFields[] = $_Vars_GameElements[$ID];
                        }
                    }
                }
                else
                {
                    $QryUpdateFleets[$DefendingFleetID[$User]] = Flights\Utils\Factories\createFleetUpdateEntry([
                        'fleetID' => $DefendingFleetID[$User],
                        'originalShips' => $Ships,
                        'postCombatShips' => $DefShips[$User],
                    ]);
                    $UserDev_UpFl[$DefendingFleetID[$User]] = Flights\Utils\Factories\createFleetDevelopmentLogEntries([
                        'originalShips' => $Ships,
                        'postCombatShips' => $DefShips[$User],
                    ]);

                    if (empty($DefShips[$User])) {
                        $DeleteFleet[] = $DefendingFleetID[$User];
                    }
                }
                $i += 1;
            }
        }

        if($StolenMet > 0)
        {
            $TargetPlanet['metal'] -= $StolenMet;
            $UserDev_UpPl[] = 'M,'.$StolenMet;
            $_FleetCache['updatePlanets'][$TargetPlanet['id']] = true;
        }
        if($StolenCry > 0)
        {
            $TargetPlanet['crystal'] -= $StolenCry;
            $UserDev_UpPl[] = 'C,'.$StolenCry;
            $_FleetCache['updatePlanets'][$TargetPlanet['id']] = true;
        }
        if($StolenDeu > 0)
        {
            $TargetPlanet['deuterium'] -= $StolenDeu;
            $UserDev_UpPl[] = 'D,'.$StolenDeu;
            $_FleetCache['updatePlanets'][$TargetPlanet['id']] = true;
        }

        foreach ($QryUpdateFleets as $fleetUpdateID => $fleetUpdateEntry) {
            $serializedFleetArray = Array2String($fleetUpdateEntry['fleet_array']);
            $serializedFleetArrayLost = Array2String($fleetUpdateEntry['fleet_array_lost']);

            if (!empty($fleetUpdateEntry['fleet_array'])) {
                if (!empty($fleetUpdateEntry['fleet_array_lost'])) {
                    if (strlen($serializedFleetArray) > strlen($serializedFleetArrayLost)) {
                        $Return['FleetArchive'][$fleetUpdateID]['Fleet_Array_Changes'] = "\"+D;{$serializedFleetArrayLost}|\"";
                    } else {
                        $Return['FleetArchive'][$fleetUpdateID]['Fleet_Array_Changes'] = "\"+L;{$serializedFleetArray}|\"";
                    }

                    $Return['FleetArchive'][$fleetUpdateID]['Fleet_Info_HasLostShips'] = '!true';
                }

                if ($fleetUpdateID != $FleetRow['fleet_id']) {
                    $_FleetCache['defFleets'][$FleetRow['fleet_end_id']][$fleetUpdateID]['fleet_array'] = $serializedFleetArray;
                }
            }

            if (
                $fleetUpdateID == $FleetRow['fleet_id'] &&
                $_FleetCache['fleetRowStatus'][$FleetRow['fleet_id']]['calcCount'] == 2
            ) {
                // Update $_FleetCache, instead of sending additional Query to Update FleetState
                // This fleet will be restored in this Calculation, so don't waste our time
                $CachePointer = &$_FleetCache['fleetRowUpdate'][$fleetUpdateID];
                $CachePointer['fleet_array'] = $serializedFleetArray;
                $CachePointer['fleet_resource_metal'] = (
                    $FleetRow['fleet_resource_metal'] +
                    $fleetUpdateEntry['fleet_resource_metal']
                );
                $CachePointer['fleet_resource_crystal'] = (
                    $FleetRow['fleet_resource_crystal'] +
                    $fleetUpdateEntry['fleet_resource_crystal']
                );
                $CachePointer['fleet_resource_deuterium'] = (
                    $FleetRow['fleet_resource_deuterium'] +
                    $fleetUpdateEntry['fleet_resource_deuterium']
                );
            } else {
                // Create UpdateFleet record for $_FleetCache
                $CachePointer = &$_FleetCache['updateFleets'][$fleetUpdateID];
                $CachePointer['fleet_array'] = $serializedFleetArray;
                $CachePointer['fleet_amount'] = $fleetUpdateEntry['fleet_amount'];
                $CachePointer['fleet_mess'] = $fleetUpdateEntry['fleet_mess'];
                $CachePointer['fleet_resource_metal'] = (
                    isset($CachePointer['fleet_resource_metal']) ?
                    $CachePointer['fleet_resource_metal'] + $fleetUpdateEntry['fleet_resource_metal'] :
                    $fleetUpdateEntry['fleet_resource_metal']
                );
                $CachePointer['fleet_resource_crystal'] = (
                    isset($CachePointer['fleet_resource_crystal']) ?
                    $CachePointer['fleet_resource_crystal'] + $fleetUpdateEntry['fleet_resource_crystal'] :
                    $fleetUpdateEntry['fleet_resource_crystal']
                );
                $CachePointer['fleet_resource_deuterium'] = (
                    isset($CachePointer['fleet_resource_deuterium']) ?
                    $CachePointer['fleet_resource_deuterium'] + $fleetUpdateEntry['fleet_resource_deuterium'] :
                    $fleetUpdateEntry['fleet_resource_deuterium']
                );
            }
        }

        if(!empty($UserDev_UpFl))
        {
            foreach($UserDev_UpFl as $FleetID => $DevArray)
            {
                if (empty($DevArray)) {
                    continue;
                }

                if($FleetID == $FleetRow['fleet_id'])
                {
                    $SetCode = '2';
                    $FleetUserID = $FleetRow['fleet_owner'];
                }
                else
                {
                    $SetCode = '3';
                    $FleetUserID = $DefendingFleetOwners[$FleetID];
                }
                $UserDev_Log[] = array('UserID' => $FleetUserID, 'PlanetID' => '0', 'Date' => $FleetRow['fleet_start_time'], 'Place' => 12, 'Code' => $SetCode, 'ElementID' => $FleetID, 'AdditionalData' => implode(';', $DevArray));
            }
        }

        // Calculate Debris & Looses
        $debrisRecoveryPercentages = [
            'ships' => ($_GameConfig['Fleet_Cdr'] / 100),
            'defenses' => ($_GameConfig['Defs_Cdr'] / 100),
        ];

        $attackersResourceLosses = Flights\Utils\Calculations\calculateResourcesLoss([
            'unitsLost' => $AtkLost,
            'debrisRecoveryPercentages' => $debrisRecoveryPercentages,
        ]);

        $RealDebrisMetalAtk += $attackersResourceLosses['realLoss']['metal'];
        $RealDebrisCrystalAtk += $attackersResourceLosses['realLoss']['crystal'];
        $RealDebrisDeuteriumAtk += $attackersResourceLosses['realLoss']['deuterium'];
        $TotalLostMetal += $attackersResourceLosses['recoverableLoss']['metal'];
        $TotalLostCrystal += $attackersResourceLosses['recoverableLoss']['crystal'];

        $defendersResourceLosses = Flights\Utils\Calculations\calculateResourcesLoss([
            'unitsLost' => $DefLost,
            'debrisRecoveryPercentages' => $debrisRecoveryPercentages,
        ]);

        $RealDebrisMetalDef += $defendersResourceLosses['realLoss']['metal'];
        $RealDebrisCrystalDef += $defendersResourceLosses['realLoss']['crystal'];
        $RealDebrisDeuteriumDef += $defendersResourceLosses['realLoss']['deuterium'];
        $TotalLostMetal += $defendersResourceLosses['recoverableLoss']['metal'];
        $TotalLostCrystal += $defendersResourceLosses['recoverableLoss']['crystal'];
        $DebrisMetalDef += $defendersResourceLosses['recoverableLoss']['metal'];
        $DebrisCrystalDef += $defendersResourceLosses['recoverableLoss']['crystal'];

        // Delete fleets (if necessary)
        if(!empty($DeleteFleet))
        {
            foreach($DeleteFleet as $FleetID)
            {
                $_FleetCache['fleetRowStatus'][$FleetID]['isDestroyed'] = true;
                if(!empty($_FleetCache['updateFleets'][$FleetID]))
                {
                    unset($_FleetCache['updateFleets'][$FleetID]);
                }
                $Return['FleetsToDelete'][] = $FleetID;
                $Return['FleetArchive'][$FleetID]['Fleet_Destroyed'] = true;
                $Return['FleetArchive'][$FleetID]['Fleet_Info_HasLostShips'] = true;
                if($FleetID == $FleetRow['fleet_id'])
                {
                    if($Result === COMBAT_DEF AND ($RealDebrisMetalDef + $RealDebrisCrystalDef + $RealDebrisDeuteriumDef) <= 0)
                    {
                        if(count($RoundsData) == 2)
                        {
                            $Return['FleetArchive'][$FleetID]['Fleet_Destroyed_Reason'] = 1;
                        }
                        else
                        {
                            $Return['FleetArchive'][$FleetID]['Fleet_Destroyed_Reason'] = 11;
                        }
                    }
                    else
                    {
                        if(count($RoundsData) == 2)
                        {
                            $Return['FleetArchive'][$FleetID]['Fleet_Destroyed_Reason'] = 12;
                        }
                        else
                        {
                            $Return['FleetArchive'][$FleetID]['Fleet_Destroyed_Reason'] = 2;
                        }
                    }
                }
                else
                {
                    unset($_FleetCache['defFleets'][$FleetRow['fleet_end_id']][$FleetID]);
                    $Return['FleetArchive'][$FleetID]['Fleet_Destroyed_Reason'] = 3;
                }
            }
        }

        if($Result === COMBAT_DRAW AND (($RealDebrisMetalDef + $RealDebrisCrystalDef + $RealDebrisDeuteriumDef) <= 0))
        {
            $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_Destroyed_Reason'] = 4;
        }

        // Create debris field on the orbit
        if ($TotalLostMetal > 0 || $TotalLostCrystal > 0) {
            Flights\Utils\FleetCache\updateGalaxyDebris([
                'debris' => [
                    'metal' => $TotalLostMetal,
                    'crystal' => $TotalLostCrystal,
                ],
                'targetPlanet' => $TargetPlanet,
                'fleetCache' => &$_FleetCache,
            ]);
        }

        // Check if Moon has been created
        $FleetDebris = $TotalLostCrystal + $TotalLostMetal;

        $MoonChance = floor($FleetDebris / COMBAT_MOONPERCENT_RESOURCES);
        if($MoonChance > 20)
        {
            $TotalMoonChance = $MoonChance;
            $MoonChance = 20;
        }
        if($MoonChance < 1)
        {
            $UserChance = 0;
        }
        elseif($MoonChance >= 1)
        {
            $UserChance = mt_rand(1, 100);
        }

        if(($UserChance > 0) AND ($UserChance <= $MoonChance))
        {
            if($TargetPlanet['planet_type'] == 1)
            {
                $CreatedMoonID = CreateOneMoonRecord($FleetRow['fleet_end_galaxy'], $FleetRow['fleet_end_system'], $FleetRow['fleet_end_planet'], $TargetUserID, '', $MoonChance);
                if($CreatedMoonID !== false)
                {
                    $TriggerTasksCheck['CREATE_MOON'] = true;
                    $MoonHasBeenCreated = true;

                    $UserDev_UpPl[] = "L,{$CreatedMoonID}";

                    // Update User Stats
                    foreach($AttackersIDs as $UserID)
                    {
                        $UserStatsData[$UserID]['moons_created'] += 1;
                    }
                }
                else
                {
                    $MoonHasBeenCreated = false;
                }
            }
            else
            {
                $MoonHasBeenCreated = false;
            }
        }
        elseif($UserChance = 0 or $UserChance > $MoonChance)
        {
            $MoonHasBeenCreated = false;
        }

        // Create DevLog Record (PlanetDefender's)
        if(!empty($UserDev_UpPl) AND !$IsAbandoned)
        {
            $UserDev_Log[] = array('UserID' => $TargetUserID, 'PlanetID' => $TargetPlanetID, 'Date' => $FleetRow['fleet_start_time'], 'Place' => 12, 'Code' => '1', 'ElementID' => '0', 'AdditionalData' => implode(';', $UserDev_UpPl));
        }

        // Morale System
        if(MORALE_ENABLED AND !$IsAbandoned AND !$IsAllyFight AND $IdleHours < (7 * 24))
        {
            $Morale_Factor = $FleetRow['morale_points'] / $TargetUser['morale_points'];
            if($Morale_Factor < 1)
            {
                $Morale_Factor = pow($Morale_Factor, -1);
                $Morale_AttackerStronger = false;
            }
            else
            {
                $Morale_AttackerStronger = true;
            }

            if($Morale_Factor > MORALE_MINIMALFACTOR)
            {
                if($Morale_AttackerStronger)
                {
                    $Morale_Update_Attacker_Type = MORALE_NEGATIVE;
                    if($Result === COMBAT_DEF OR $Result === COMBAT_DRAW)
                    {
                        $Morale_Update_Defender = true;
                    }
                }
                else
                {
                    $Morale_Update_Attacker_Type = MORALE_POSITIVE;
                }

                $Morale_Updated = Morale_AddMorale($FleetRow, $Morale_Update_Attacker_Type, $Morale_Factor, 1, 1, $FleetRow['fleet_start_time']);
                if($Morale_Updated)
                {
                    $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['level'] = $FleetRow['morale_level'];
                    $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['droptime'] = $FleetRow['morale_droptime'];
                    $_FleetCache['MoraleCache'][$FleetRow['fleet_owner']]['lastupdate'] = $FleetRow['morale_lastupdate'];

                    $ReportData['morale'][$FleetRow['fleet_owner']] = array
                    (
                        'usertype' => 'atk',
                        'type' => $Morale_Update_Attacker_Type,
                        'factor' => $Morale_Factor,
                        'level' => $FleetRow['morale_level']
                    );
                }

                if($Morale_Update_Defender === true)
                {
                    if($Result === COMBAT_DRAW)
                    {
                        $Morale_LevelFactor = 1/2;
                        $Morale_TimeFactor = 1/2;
                    }
                    else
                    {
                        $Morale_LevelFactor = 1;
                        $Morale_TimeFactor = 1;
                    }

                    $Morale_Updated = Morale_AddMorale($TargetUser, MORALE_POSITIVE, $Morale_Factor, $Morale_LevelFactor, $Morale_TimeFactor, $FleetRow['fleet_start_time']);
                    if($Morale_Updated)
                    {
                        $_FleetCache['MoraleCache'][$TargetUser['id']]['level'] = $TargetUser['morale_level'];
                        $_FleetCache['MoraleCache'][$TargetUser['id']]['droptime'] = $TargetUser['morale_droptime'];
                        $_FleetCache['MoraleCache'][$TargetUser['id']]['lastupdate'] = $TargetUser['morale_lastupdate'];

                        $ReportData['morale'][$TargetUser['id']] = array
                        (
                            'usertype' => 'def',
                            'type' => MORALE_POSITIVE,
                            'factor' => $Morale_Factor,
                            'level' => $TargetUser['morale_level']
                        );
                    }
                }
            }
        }

        // CREATE BATTLE REPORT
        $ReportData['init']['usr']['atk'] = $AttackersData;
        $ReportData['init']['usr']['def'] = $DefendersData;
        $ReportData['init']['time'] = $totaltime;
        $ReportData['init']['date'] = $FleetRow['fleet_start_time'];

        $ReportData['init']['result'] = $Result;
        $ReportData['init']['met'] = $StolenMet;
        $ReportData['init']['cry'] = $StolenCry;
        $ReportData['init']['deu'] = $StolenDeu;
        $ReportData['init']['deb_met'] = $TotalLostMetal;
        $ReportData['init']['deb_cry'] = $TotalLostCrystal;
        $ReportData['init']['moon_chance'] = $MoonChance;
        $ReportData['init']['moon_created'] = $MoonHasBeenCreated;
        $ReportData['init']['total_moon_chance'] = $TotalMoonChance;
        $ReportData['init']['moon_destroyed'] = false;
        $ReportData['init']['moon_des_chance'] = 0;
        $ReportData['init']['fleet_destroyed'] = false;
        $ReportData['init']['fleet_des_chance'] = 0;
        $ReportData['init']['planet_name'] = $TargetPlanetGetName;
        $ReportData['init']['onMoon'] = ($FleetRow['fleet_end_type'] == 3 ? true : false);
        $ReportData['init']['atk_lost'] = $RealDebrisMetalAtk + $RealDebrisCrystalAtk + $RealDebrisDeuteriumAtk;
        $ReportData['init']['def_lost'] = $RealDebrisMetalDef + $RealDebrisCrystalDef + $RealDebrisDeuteriumDef;

        foreach($RoundsData as $RoundKey => $RoundData)
        {
            foreach($RoundData as $MainKey => $RoundData2)
            {
                if(!empty($RoundData2['ships']))
                {
                    foreach($RoundData2['ships'] as $UserKey => $UserData)
                    {
                        $RoundsData[$RoundKey][$MainKey]['ships'][$UserKey] = Array2String($UserData);
                    }
                }
            }
        }
        $ReportData['rounds'] = $RoundsData;

        if(count($RoundsData) <= 2 AND $Result === COMBAT_DEF)
        {
            $DisallowAttackers = true;
        }
        else
        {
            $DisallowAttackers = false;
        }

        $CreatedReport = CreateBattleReport($ReportData, array('atk' => $AttackersIDs, 'def' => $DefendersIDs), $DisallowAttackers);
        $ReportID = $CreatedReport['ID'];

        $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_ReportID'] = $ReportID;
        if(!empty($DefendingFleetID))
        {
            foreach($DefendingFleetID as $FleetID)
            {
                $Return['FleetArchive'][$FleetID]['Fleet_DefenderReportIDs'] = "\"+,{$ReportID}\"";
            }
        }

        // Update battle stats & set Battle Report colors
        if(!$IsAllyFight)
        {
            if($Result === COMBAT_ATK)
            {
                foreach($AttackersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_won'] += 1;
                }
                foreach($DefendersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_lost'] += 1;
                }
            }
            elseif($Result === COMBAT_DRAW)
            {
                foreach($AttackersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_draw'] += 1;
                }
                foreach($DefendersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_draw'] += 1;
                }
            }
            elseif($Result === COMBAT_DEF)
            {
                foreach($AttackersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_lost'] += 1;
                }
                foreach($DefendersIDs as $UserID)
                {
                    $UserStatsData[$UserID]['raids_won'] += 1;
                }
            }

            // Update User Destroyed & Lost Stats
            Flights\Utils\FleetCache\applyCombatUnitStats([
                'userStats' => &$UserStatsData,
                'combatShotdownResult' => $ShotDown,
                'mainAttackerUserID' => $FleetRow['fleet_owner'],
                'mainDefenderUserID' => $TargetUser['id'],
                'attackingFleetIDs' => [],
                'attackingFleetOwnerIDs' => [],
                'defendingFleetIDs' => $DefendingFleetID,
                'defendingFleetOwnerIDs' => $DefendingFleetOwners,
            ]);

            if(!empty($ShotDown))
            {
                if(!empty($ShotDown['atk']['d'][0]))
                {
                    if(!isset($TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS']))
                    {
                        $TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS'] = 0;
                    }
                    if(!isset($TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO']))
                    {
                        $TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO'] = 0;
                    }
                    foreach($ShotDown['atk']['d'][0] as $ShipID => $ShipCount)
                    {
                        $TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO'] += (($_Vars_Prices[$ShipID]['metal'] + $_Vars_Prices[$ShipID]['crystal'] + $_Vars_Prices[$ShipID]['deuterium']) * $ShipCount);
                        if(in_array($ShipID, $_Vars_ElementCategories['units']['military']))
                        {
                            $TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS'] += $ShipCount;
                        }
                    }
                }
            }

            if($Result === COMBAT_ATK)
            {
                $TriggerTasksCheck['BATTLE_WIN'] = true;
                $TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_TOTALLIMIT'] = true;
                $TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_LIMIT'] = true;
                $TriggerTasksCheck['BATTLE_WINORDRAW_LIMIT'] = true;
            }
            elseif($Result === COMBAT_DRAW)
            {
                $TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_TOTALLIMIT'] = true;
                $TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_LIMIT'] = true;
                $TriggerTasksCheck['BATTLE_WINORDRAW_LIMIT'] = true;
            }
            elseif($Result === COMBAT_DEF)
            {
                $TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO'] = 0;
            }
        }
        else
        {
            if($MoonHasBeenCreated)
            {
                $TriggerTasksCheck['CREATE_MOON_FRIENDLY'] = true;
            }
            unset($TriggerTasksCheck['BATTLE_COLLECT_METAL']);
            unset($TriggerTasksCheck['BATTLE_COLLECT_CRYSTAL']);
            unset($TriggerTasksCheck['BATTLE_COLLECT_DEUTERIUM']);

            foreach($AttackersIDs as $UserID)
            {
                $UserStatsData[$UserID]['raids_inAlly'] += 1;
            }
            foreach($DefendersIDs as $UserID)
            {
                $UserStatsData[$UserID]['raids_inAlly'] += 1;
            }
        }

        $messageJSON = Flights\Utils\Factories\createCombatResultForAttackersMessage([
            'missionType' => 1,
            'report' => $CreatedReport,
            'combatResult' => $Result,
            'totalAttackersResourcesLoss' => $attackersResourceLosses,
            'totalDefendersResourcesLoss' => $defendersResourceLosses,
            'totalResourcesPillage' => $resourcesPillage,
            'fleetRow' => $FleetRow,
        ]);

        Cache_Message($CurrentUserID, 0, $FleetRow['fleet_start_time'], 3, '003', '012', $messageJSON);

        if (!$IsAbandoned) {
            $messageJSON = Flights\Utils\Factories\createCombatResultForMainDefenderMessage([
                'report' => $CreatedReport,
                'combatResult' => $Result,
                'fleetRow' => $FleetRow,
                'rebuiltElements' => Collections\compact($rebuiltDefenseSystems),
                'hasLostAnyDefenseSystems' => Flights\Utils\Helpers\hasLostAnyDefenseSystem([
                    'originalShips' => $DefendingFleets,
                    'postCombatShips' => $DefShips,
                ]),
            ]);

            Cache_Message($TargetUserID, 0, $FleetRow['fleet_start_time'], 3, '003', '012', $messageJSON);
        }

        if (count($DefendersIDs) > 1) {
            $messageJSON = Flights\Utils\Factories\createCombatResultForAlliedDefendersMessage([
                'report' => $CreatedReport,
                'combatResult' => $Result,
                'fleetRow' => $FleetRow,
            ]);

            unset($DefendersIDs[0]);

            Cache_Message($DefendersIDs, 0, $FleetRow['fleet_start_time'], 3, '003', '017', $messageJSON);
        }

        if(!empty($TriggerTasksCheck))
        {
            global $GlobalParsedTasks, $_User;

            if($_User['id'] == $FleetRow['fleet_owner'])
            {
                $ThisTaskUser = $_User;
            }
            else
            {
                if(empty($GlobalParsedTasks[$FleetRow['fleet_owner']]['tasks_done_parsed']))
                {
                    $GetUserTasksDone['tasks_done'] = $_FleetCache['userTasks'][$FleetRow['fleet_owner']];
                    Tasks_CheckUservar($GetUserTasksDone);
                    $GlobalParsedTasks[$FleetRow['fleet_owner']] = $GetUserTasksDone;
                }
                $ThisTaskUser = $GlobalParsedTasks[$FleetRow['fleet_owner']];
                $ThisTaskUser['id'] = $FleetRow['fleet_owner'];
            }

            if(isset($TriggerTasksCheck['BATTLE_WIN']))
            {
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_WIN', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_TOTALLIMIT']) && $TotalMoonChance > 0)
            {
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_WINORDRAW_SOLO_TOTALLIMIT', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TotalMoonChance)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, $TotalMoonChance);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_LIMIT']) || isset($TriggerTasksCheck['BATTLE_WINORDRAW_LIMIT']))
            {
                $Debris_Total_Def = ($DebrisMetalDef + $DebrisCrystalDef) / COMBAT_MOONPERCENT_RESOURCES;
                if(isset($TriggerTasksCheck['BATTLE_WINORDRAW_SOLO_LIMIT']))
                {
                    Tasks_TriggerTask($ThisTaskUser, 'BATTLE_WINORDRAW_SOLO_LIMIT', array
                    (
                        'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $Debris_Total_Def)
                        {
                            if($JobArray['minimalEnemyPercentLimit'] > $Debris_Total_Def)
                            {
                                return true;
                            }
                            return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                        }
                    ));
                }
                if(isset($TriggerTasksCheck['BATTLE_WINORDRAW_LIMIT']))
                {
                    Tasks_TriggerTask($ThisTaskUser, 'BATTLE_WINORDRAW_LIMIT', array
                    (
                        'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $Debris_Total_Def)
                        {
                            if($JobArray['minimalEnemyPercentLimit'] > $Debris_Total_Def)
                            {
                                return true;
                            }
                            return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                        }
                    ));
                }
            }
            if(isset($TriggerTasksCheck['BATTLE_COLLECT_METAL']) && $TriggerTasksCheck['BATTLE_COLLECT_METAL'] > 0)
            {
                $TaskTemp = $TriggerTasksCheck['BATTLE_COLLECT_METAL'];
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_COLLECT_METAL', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TaskTemp)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, $TaskTemp);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_COLLECT_CRYSTAL']) && $TriggerTasksCheck['BATTLE_COLLECT_CRYSTAL'] > 0)
            {
                $TaskTemp = $TriggerTasksCheck['BATTLE_COLLECT_CRYSTAL'];
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_COLLECT_CRYSTAL', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TaskTemp)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, $TaskTemp);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_COLLECT_DEUTERIUM']) && $TriggerTasksCheck['BATTLE_COLLECT_DEUTERIUM'] > 0)
            {
                $TaskTemp = $TriggerTasksCheck['BATTLE_COLLECT_DEUTERIUM'];
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_COLLECT_DEUTERIUM', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TaskTemp)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, $TaskTemp);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['CREATE_MOON']))
            {
                Tasks_TriggerTask($ThisTaskUser, 'CREATE_MOON', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['CREATE_MOON_FRIENDLY']))
            {
                Tasks_TriggerTask($ThisTaskUser, 'CREATE_MOON_FRIENDLY', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS']) && $TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS'] > 0)
            {
                $TaskTemp = $TriggerTasksCheck['BATTLE_DESTROY_MILITARYUNITS'];
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_DESTROY_MILITARYUNITS', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TaskTemp)
                    {
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, $TaskTemp);
                    }
                ));
            }
            if(isset($TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO']) && $TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO'] > 0)
            {
                $TaskTemp2 = 0;
                foreach($AttackingFleets[0] as $ShipID => $ShipCount)
                {
                    $TaskTemp2 += (($_Vars_Prices[$ShipID]['metal'] + $_Vars_Prices[$ShipID]['crystal'] + $_Vars_Prices[$ShipID]['deuterium']) * $ShipCount);
                }
                $TaskTemp = $TriggerTasksCheck['BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO'];
                Tasks_TriggerTask($ThisTaskUser, 'BATTLE_DESTROY_MOREEXPENSIVEFLEET_SOLO', array
                (
                    'mainCheck' => function($JobArray, $ThisCat, $TaskID, $JobID) use ($ThisTaskUser, $TaskTemp, $TaskTemp2)
                    {
                        if($JobArray['minimalEnemyCost'] > $TaskTemp)
                        {
                            return true;
                        }
                        if($TaskTemp2 > ($TaskTemp * $JobArray['maximalOwnValue']))
                        {
                            return true;
                        }
                        return Tasks_TriggerTask_MainCheck_Progressive($JobArray, $ThisCat, $TaskID, $JobID, $ThisTaskUser, 1);
                    }
                ));
            }
        }
    }

    if($FleetRow['calcType'] == 3 && (!isset($_FleetCache['fleetRowStatus'][$FleetRow['fleet_id']]['isDestroyed']) || $_FleetCache['fleetRowStatus'][$FleetRow['fleet_id']]['isDestroyed'] !== true))
    {
        if(!empty($_FleetCache['fleetRowUpdate'][$FleetRow['fleet_id']]))
        {
            foreach($_FleetCache['fleetRowUpdate'][$FleetRow['fleet_id']] as $Key => $Value)
            {
                $FleetRow[$Key] = $Value;
            }
        }
        $Return['FleetsToDelete'][] = $FleetRow['fleet_id'];
        $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_Calculated_ComeBack'] = true;
        $Return['FleetArchive'][$FleetRow['fleet_id']]['Fleet_Calculated_ComeBack_Time'] = $Now;
        RestoreFleetToPlanet($FleetRow, true, $_FleetCache);
    }

    return $Return;
}

?>
