<?php

namespace modules\user\common\rbac;

use Yii;
use modules\user\common\models\User;

class UserTypeRule extends \yii\rbac\Rule
{
    public $name = 'userType';

    public function execute($user, $item, $params)
    {
        if (!Yii::$app->user->isGuest) {
            $userType = Yii::$app->user->identity->type;
            switch ($item->name) {
                case 'superuser':
                    return $userType == User::TYPE_SUPERUSER;
                case 'editor':
                    return $userType == User::TYPE_EDITOR
                        || $userType == User::TYPE_SUPERUSER;
                case 'operator':
                    return $userType == User::TYPE_OPERATOR
                        || $userType == User::TYPE_EDITOR
                        || $userType == User::TYPE_SUPERUSER;
                case 'expert':
                    return $userType == User::TYPE_EXPERT
                        || $userType == User::TYPE_DEPARTMENT_MANAGER_PROCESS
                        || $userType == User::TYPE_DEPARTMENT_MANAGER_ENGINEERING
                        || $userType == User::TYPE_OPERATOR
                        || $userType == User::TYPE_SUPERUSER;
                case 'process_department_manager':
                    return $userType == User::TYPE_DEPARTMENT_MANAGER_PROCESS
                        || $userType == User::TYPE_SUPERUSER;
                case 'engineering_department_manager':
                    return $userType == User::TYPE_DEPARTMENT_MANAGER_ENGINEERING
                        || $userType == User::TYPE_SUPERUSER;
                default:
                    break;
            }
        }
        return false;
    }
}
