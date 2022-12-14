<?php

// TODO: Migrate to IIFE once PHP 5 support is removed
call_user_func(function () {
    global $_EnginePath;

    $includePath = $_EnginePath . 'modules/attackSimulator/';

    include($includePath . './components/MoraleInput/MoraleInput.component.php');
    include($includePath . './components/MoraleInputsSection/MoraleInputsSection.component.php');
    include($includePath . './components/ShipInput/ShipInput.component.php');
    include($includePath . './components/TechInput/TechInput.component.php');
    include($includePath . './components/TechInputsSection/TechInputsSection.component.php');
    include($includePath . './components/UnitInputsSection/UnitInputsSection.component.php');

    include($includePath . './utils/combatTechs.utils.php');

});

?>
