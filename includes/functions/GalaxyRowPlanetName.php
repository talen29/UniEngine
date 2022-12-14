<?php

use UniEngine\Engine\Includes\Helpers\World\Checks;

function GalaxyRowPlanetName($GalaxyRow, $GalaxyRowPlanet, $GalaxyRowUser, $Galaxy, $System, $Planet, $MyBuddies)
{
    global $_Lang, $_User, $SensonPhalanxLevel, $CurrentSystem, $CurrentGalaxy, $Time;
    static $TPLPlanet = false, $TPLEmpty = false;

    if ($GalaxyRow['id_planet'] <= 0) {
        if ($TPLEmpty === false) {
            $TPLEmpty = gettemplate('galaxy_row_planetempty');
        }

        $ParseEmpty = array('Galaxy' => $Galaxy, 'System' => $System, 'Planet' => $Planet, 'MissionText' => $_Lang['type_mission'][7]);

        return parsetemplate($TPLEmpty, $ParseEmpty);
    }

    $PlanetType = 1;
    $Now = $Time;
    $Activity = '';
    $NameColor = '';
    if($TPLPlanet === false)
    {
        $TPLPlanet = gettemplate('galaxy_row_planetname');
    }

    if($GalaxyRowUser['id'] == $_User['id'])
    {
        $NameColor = 'skyblue';
    }
    else
    {
        if($GalaxyRowPlanet['id_owner'] > 0)
        {
            if($GalaxyRowUser['ally_id'] == $_User['ally_id'] AND $_User['ally_id'] > 0)
            {
                $NameColor = 'lime';
            }
            elseif(in_array($GalaxyRowUser['id'], $MyBuddies))
            {
                $NameColor = 'green';
            }
        }
        else
        {
            $NameColor = 'red';
            $GalaxyRowPlanet['name'] = $_Lang['gl_destroyedplanet'];
        }
        $UpdateDiff = $Now - $GalaxyRowPlanet['last_update'];
        if($UpdateDiff < 3600)
        {
            if($UpdateDiff < TIME_ONLINE)
            {
                $Activity = '*';
            }
            else
            {
                $Activity = pretty_time_hour($UpdateDiff, true);
            }
            $Activity = "({$Activity})";
        }
    }
    if($GalaxyRow['hide_planet'] == 1)
    {
        $NameColor = 'orange';
    }

    $Parse = array('NameColor' => $NameColor, 'PlanetName' => $GalaxyRowPlanet['name'], 'Activity' => $Activity);
    if($GalaxyRowUser['id'] != $_User['id'])
    {
        $Parse['AddHref'] = "href=\"fleet.php?galaxy={$Galaxy}&system={$System}&planet={$Planet}&planettype={$PlanetType}&target_mission=1\"";
        $Parse['AddTitle'] = "title=\"{$_Lang['gl_attack']}\"";

        if($GalaxyRowPlanet['galaxy'] == $CurrentGalaxy AND $SensonPhalanxLevel > 0)
        {
            $isInRange = Checks\isTargetInRange([
                'originPosition' => $CurrentSystem,
                'targetPosition' => $System,
                'range' => GetPhalanxRange($SensonPhalanxLevel),
            ]);

            if ($isInRange) {
                $Parse['AddHref'] = 'href="#"';
                $Parse['AddOnClick'] = "onclick=\"return Phalanx({$Galaxy}, {$System}, {$Planet}, {$PlanetType});\"";
                $Parse['AddTitle'] = "title=\"{$_Lang['gl_phalanx']}\"";
            }
        }
    }
    else
    {
        $Parse['AddHref'] = "href=\"fleet.php?galaxy={$Galaxy}&system={$System}&planet={$Planet}&planettype={$PlanetType}&target_mission=3\"";
        $Parse['AddTitle'] = "title=\"{$_Lang['gl_transport']}\"";
    }

    return parsetemplate($TPLPlanet, $Parse);
}

?>
