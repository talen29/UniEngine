<?php

// TODO: Migrate to IIFE once PHP 5 support is removed
call_user_func(function () {
    global $_EnginePath;

    $includePath = $_EnginePath . 'modules/flightControl/';

    include($includePath . './components/AvailableMissionsList/AvailableMissionsList.component.php');
    include($includePath . './components/SmartFleetBlockadeInfoBox/SmartFleetBlockadeInfoBox.component.php');
    include($includePath . './components/SmartFleetBlockadeInfoBox/SmartFleetBlockadeInfoBox.utils.php');
    include($includePath . './components/TargetOptionLabel/TargetOptionLabel.component.php');
    include($includePath . './components/TargetsSelector/TargetsSelector.component.php');

    include($includePath . './enums/RetreatResultType.enum.php');

    include($includePath . './screens/SendWizardStepOne/SendWizardStepOne.screen.php');
    include($includePath . './screens/SendWizardStepOne/components/UnionManagement/utils/createNewUnion.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/UnionManagement/utils/getBaseUnionData.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/UnionManagement/utils/updateUnionName.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/UnionManagement/UnionManagement.component.php');
    include($includePath . './screens/SendWizardStepOne/components/UnionMembersListOption/UnionMembersListOption.component.php');
    include($includePath . './screens/SendWizardStepOne/components/RetreatInfoBox/RetreatInfoBox.component.php');
    include($includePath . './screens/SendWizardStepOne/components/AvailableShipsList/AvailableShipsList.component.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/FlightsList.component.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/buildFriendlyAcsListElement.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/buildOwnListElement.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/dataFetchers.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/extractors.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/paramGetters.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/prerenderFriendlyAcsListElement.utils.php');
    include($includePath . './screens/SendWizardStepOne/components/FlightsList/utils/prerenderOwnListElement.utils.php');

    include($includePath . './screens/SendWizardStepTwo/components/SpeedSelector/SpeedSelector.component.php');

    include($includePath . './screens/Shortcuts/Shortcuts.screen.php');
    include($includePath . './screens/Shortcuts/components/ListManagement/ListManagement.component.php');
    include($includePath . './screens/Shortcuts/components/ShortcutManagementForm/ShortcutManagementForm.component.php');
    include($includePath . './screens/Shortcuts/commands/upsertShortcut.command.php');
    include($includePath . './screens/Shortcuts/commands/deleteShortcut.command.php');

    include($includePath . './utils/checks/hasMetPushAlertConditions.checks.php');
    include($includePath . './utils/factories/createAlertFiltersSearchParams.factory.php');
    include($includePath . './utils/factories/createFleetDevLogEntry.factory.php');
    include($includePath . './utils/factories/createFleetUsersStatsData.factory.php');
    include($includePath . './utils/factories/createMultiAlert.factory.php');
    include($includePath . './utils/factories/createPlanetShipsJSObject.factory.php');
    include($includePath . './utils/factories/createPushAlert.factory.php');
    include($includePath . './utils/factories/createQuantumGateFuelJSObject.factory.php');
    include($includePath . './utils/factories/createUnionInvitationMessage.factory.php');
    include($includePath . './utils/fetchers/fetchActiveSmartFleetsBlockadeEntries.fetcher.php');
    include($includePath . './utils/fetchers/fetchBashValidatorFlightLogEntries.fetcher.php');
    include($includePath . './utils/fetchers/fetchJoinableUnionFlights.fetcher.php');
    include($includePath . './utils/fetchers/fetchMultiDeclaration.fetcher.php');
    include($includePath . './utils/fetchers/fetchPlanetOwnerDetails.fetcher.php');
    include($includePath . './utils/fetchers/fetchSavedShortcuts.fetcher.php');
    include($includePath . './utils/fetchers/fetchTargetGalaxyDetails.fetcher.php');
    include($includePath . './utils/fetchers/fetchUnionFleet.fetcher.php');
    include($includePath . './utils/fetchers/fetchUnionInvitablePlayers.fetcher.php');
    include($includePath . './utils/fetchers/fetchUnionMissingUsersData.fetcher.php');
    include($includePath . './utils/fetchers/fetchUsersWithMatchingIp.fetcher.php');
    include($includePath . './utils/helpers/calculateCargoFleetArray.helper.php');
    include($includePath . './utils/helpers/extractUnionFleetIds.helper.php');
    include($includePath . './utils/helpers/extractUnionMembersDetails.helper.php');
    include($includePath . './utils/helpers/extractUnionMembersModification.helper.php');
    include($includePath . './utils/helpers/getAvailableExpeditionTimes.helper.php');
    include($includePath . './utils/helpers/getAvailableHoldTimes.helper.php');
    include($includePath . './utils/helpers/getAvailableSpeeds.helper.php');
    include($includePath . './utils/helpers/getFleetArrayInfo.helper.php');
    include($includePath . './utils/helpers/getFleetsInFlightCounters.helper.php');
    include($includePath . './utils/helpers/getFleetUnionJoinData.helper.php');
    include($includePath . './utils/helpers/getFlightParams.helper.php');
    include($includePath . './utils/helpers/getQuantumGateStateDetails.helper.php');
    include($includePath . './utils/helpers/getSlowestShipSpeed.helper.php');
    include($includePath . './utils/helpers/getTargetInfo.helper.php');
    include($includePath . './utils/helpers/getUserExpeditionSlotsCount.helper.php');
    include($includePath . './utils/helpers/getUserFleetSlotsCount.helper.php');
    include($includePath . './utils/helpers/getValidMissionTypes.helper.php');
    include($includePath . './utils/helpers/noobProtection.helper.php');
    include($includePath . './utils/helpers/tryJoinUnion.helper.php');
    include($includePath . './utils/inputs/normalizeFleetResources.inputs.php');
    include($includePath . './utils/inputs/normalizeGobackFleetArray.inputs.php');
    include($includePath . './utils/updaters/createUnionEntry.updaters.php');
    include($includePath . './utils/updaters/fleetArchiveACSEntries.updaters.php');
    include($includePath . './utils/updaters/fleetArchiveEntryPersist.updaters.php');
    include($includePath . './utils/updaters/fleetPersist.updaters.php');
    include($includePath . './utils/updaters/updateFleetArchiveAcsId.updaters.php');
    include($includePath . './utils/updaters/updateFleetOriginPlanet.updaters.php');
    include($includePath . './utils/updaters/updateUnionEntry.updaters.php');
    include($includePath . './utils/updaters/updateUnionFleets.updaters.php');
    include($includePath . './utils/updaters/updateUnionMembers.updaters.php');
    include($includePath . './utils/validators/bashLimit.validator.php');
    include($includePath . './utils/validators/fleetArray.validator.php');
    include($includePath . './utils/validators/fleetResources.validator.php');
    include($includePath . './utils/validators/flightSlots.validator.php');
    include($includePath . './utils/validators/joinUnion.validator.php');
    include($includePath . './utils/validators/missionExpedition.validator.php');
    include($includePath . './utils/validators/missionHold.validator.php');
    include($includePath . './utils/validators/noobProtection.validator.php');
    include($includePath . './utils/validators/quantumGate.validator.php');
    include($includePath . './utils/validators/smartFleetsBlockadeState.validator.php');
    include($includePath . './utils/validators/targetOwner.validator.php');
    include($includePath . './utils/errors/bashLimit.utils.php');
    include($includePath . './utils/errors/fleetArray.utils.php');
    include($includePath . './utils/errors/fleetResources.utils.php');
    include($includePath . './utils/errors/flightSlots.utils.php');
    include($includePath . './utils/errors/joinUnion.utils.php');
    include($includePath . './utils/errors/noobProtection.utils.php');
    include($includePath . './utils/errors/quantumGate.utils.php');
    include($includePath . './utils/errors/smartFleetsBlockade.utils.php');
    include($includePath . './utils/errors/targetOwner.utils.php');
    include($includePath . './utils/errors/tryJoinUnion.utils.php');

});

?>
