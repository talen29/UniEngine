<?php

$includePath = $_EnginePath . 'common/';

include($includePath . './exceptions.php');

include($includePath . './components/GalaxyPlanetLink/GalaxyPlanetLink.component.php');

include($includePath . './modules/uni/utils/isEmailDomainBanned.util.php');

include($includePath . './utils/routing/galaxy.routing.php');

?>
