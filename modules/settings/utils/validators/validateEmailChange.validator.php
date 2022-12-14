<?php

namespace UniEngine\Engine\Modules\Settings\Utils\Validators;

use UniEngine\Engine\Common\Modules\Uni;
use UniEngine\Engine\Modules\Settings;

/**
 * @param array $params
 * @param array $params['input']
 * @param string $params['input']['newEmailAddress']
 * @param string $params['input']['newEmailAddressConfirm']
 * @param arrayRef $params['currentUser']
 * @param boolean $params['isAlreadyChangingEmail']
 */
function validateEmailChange($params) {
    $currentUser = &$params['currentUser'];
    $isAlreadyChangingEmail = $params['isAlreadyChangingEmail'];

    $executor = function ($input, $resultHelpers) use (&$currentUser, $isAlreadyChangingEmail) {
        $newEmailAddress = $input['newEmailAddress'];
        $newEmailAddressConfirm = $input['newEmailAddressConfirm'];

        $currentUserEmail = $currentUser['email'];

        if ($isAlreadyChangingEmail) {
            return $resultHelpers['createFailure']([
                'code' => 'EMAIL_CHANGE_IN_PROGRESS',
            ]);
        }

        if (!is_email($newEmailAddress)) {
            return $resultHelpers['createFailure']([
                'code' => 'INVALID_EMAIL',
            ]);
        }
        if ($newEmailAddress === $currentUserEmail) {
            return $resultHelpers['createFailure']([
                'code' => 'NEW_EMAIL_SAME_AS_OLD',
            ]);
        }
        if ($newEmailAddress !== $newEmailAddressConfirm) {
            return $resultHelpers['createFailure']([
                'code' => 'NEW_EMAIL_CONFIRMATION_INVALID',
            ]);
        }
        if (Uni\Utils\isEmailDomainBanned($newEmailAddress)) {
            return $resultHelpers['createFailure']([
                'code' => 'BANNED_DOMAIN_USED',
            ]);
        }

        $fetchExistingEmailFromDB = Settings\Utils\Queries\getUserWithEmailAddress([
            'emailAddress' => $newEmailAddress,
        ]);

        if ($fetchExistingEmailFromDB) {
            // TODO: Verify whether we should fetch email change processes as well
            return $resultHelpers['createFailure']([
                'code' => 'NEW_EMAIL_ALREADY_IN_USE',
            ]);
        }

        return $resultHelpers['createSuccess']([]);
    };

    return createFuncWithResultHelpers($executor)($params['input']);
}

?>
