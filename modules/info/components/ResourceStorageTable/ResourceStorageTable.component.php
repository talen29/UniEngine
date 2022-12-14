<?php

namespace UniEngine\Engine\Modules\Info\Components\ResourceStorageTable;

use UniEngine\Engine\Includes\Helpers\World;
use UniEngine\Engine\Modules\Info;

/**
 * @param array $props
 * @param string $props['elementId']
 * @param arrayRef $props['planet']
 */
function render($props) {
    $elementId = $props['elementId'];
    $planet = &$props['planet'];
    $user = [];

    $localTemplateLoader = createLocalTemplateLoader(__DIR__);
    $rowTpl = $localTemplateLoader('storageRow');

    $currentLevel = World\Elements\getElementCurrentLevel($elementId, $planet, $user);

    $currentLevelCapacity = getElementStorageCapacities($elementId, $planet, []);

    $tableRange = Info\Utils\getLevelRange([
        'currentLevel' => $currentLevel,
        'rangeLengthLeft' => 3,
        'rangeLengthRight' => 6,
    ]);

    // Supports only one resource type
    $capacityResourceKey = getElementStoredResourceKeys($elementId)[0];

    $storageRows = [];

    for (
        $iterLevel = $tableRange['startLevel'];
        $iterLevel <= $tableRange['endLevel'];
        $iterLevel++
    ) {
        $rowData = [];

        if ($iterLevel == $currentLevel) {
            $rowData['build_lvl'] = "<span class=\"red\">{$iterLevel}</span>";
            $rowData['IsCurrent'] = ' class="thisLevel"';
        } else {
            $rowData['build_lvl'] = $iterLevel;
        }

        $iterLevelCapacity = getElementStorageCapacities(
            $elementId,
            $planet,
            [
                'customLevel' => $iterLevel
            ]
        );

        $resourceCapacity = $iterLevelCapacity[$capacityResourceKey];
        $capacityDifference = ($resourceCapacity - $currentLevelCapacity[$capacityResourceKey]);

        $rowData['build_capacity'] = prettyNumber($resourceCapacity);
        $rowData['build_capacity_diff'] = prettyColorNumber(floor($capacityDifference));

        $storageRows[] = parsetemplate($rowTpl, $rowData);
    }

    return [
        'componentHTML' => implode('', $storageRows),
    ];
}

?>
