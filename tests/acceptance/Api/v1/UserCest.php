<?php

namespace AcceptanceTests\Api\v1;

use App\Tests\AcceptanceTester;

class UserCest
{
    public function testAddUserAction(AcceptanceTester $I): void
    {
        $I->sendPost('/api/v4/save-user', [
            'login' => 'my_user',
            'password' => 'my_password',
            'roles' => '["ROLE_USER"]',
            'age' => 23,
            'isActive' => 'true',
        ]);
        $I->canSeeResponseMatchesJsonType(['id' => 'integer:>0']);
    }
}
