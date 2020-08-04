<?php

namespace modules\user\backend\models;

use Yii;
use nad\office\modules\expert\models\Expert;
use modules\user\common\models\User as BaseUser;

class User extends BaseUser
{
    public $personnelId;

    public function rules()
    {
        return [
            [['email', 'name', 'surname', 'identityCode', 'personnelId'], 'trim'],
            ['email', 'email'],
            [['status', 'type'], 'integer'],
            ['phone', 'string', 'max' => 11, 'min' => 4],
            [
                'phone',
                'match',
                'pattern' => '([0-9]{4,11})',
                'message' => 'لطفا شماره را به طور صحیح وارد کنید.'

            ],
            [['email', 'name', 'surname', 'identityCode', 'post', 'personnelId'], 'string', 'max' => 255],
            [['email', 'password', 'personnelId'], 'required'],
            ['password', 'string', 'min' => 6],
            ['password', 'match',
                'pattern' => '((?=.*\d)(?=.*[a-zA-Z]).{6,20})',
                'message' => 'کلمه عبور باید شامل حروف و حداقل یک عدد یا سمبل دیگر باشد. '.
                             'طول کلمه عبور باید بین ۶ و ۲۰ کاراکتر باشد.',
            ],
            ['email', 'unique', 'message' => 'این آدرس ایمیل قبلا استفاده شده است.']
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['changePassword'] = ['password'];
        $scenarios['update'] = ['email', 'status', 'type', 'phone', 'name', 'surname', 'personnelId'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'شناسه',
            'email' => 'نام کاربری (ایمیل)',
            'phone' => 'شماره تماس',
            'name' => 'نام',
            'title' => 'نام',
            'surname' => 'نام خانوادگی',
            'identityCode' => 'ایمیل',
            'post' => 'سمت',
            'status' => 'وضعیت',
            'type' => 'نوع کاربر',
            'password' => 'کلمه عبور',
            'originalPassword' => 'کلمه عبور',
            'createdAt' => 'تاریخ ثبت‌نام',
            'updatedAt' => 'تاریخ آخرین بروزرسانی',
            'lastLoggedInAt' => 'تاریخ آخرین ورود',
            'failedAttempts' => 'دفعات تلاش ناموفق برای ورود',
            'personnelId' => 'شماره پرسنلی'
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function afterDelete()
    {
        Yii::$app->db->createCommand()->delete('auth_assignment', [
            'user_id' => $this->id
        ])->execute();
        parent::afterDelete();
    }

    // role assignment to user
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $authManager = Yii::$app->authManager;
            $permission = '';
            switch ($this->type) {
                case self::TYPE_EXPERT:
                    $permission = $authManager->getRole('expert');
                    break;
                case self::TYPE_SUPERUSER:
                    $permission = $authManager->getRole('superuser');
                    break;
                case self::TYPE_DEPARTMENT_MANAGER_PROCESS:
                    $permission = $authManager->getRole('process_department_manager');
                    break;
                case self::TYPE_DEPARTMENT_MANAGER_ENGINEERING:
                    $permission = $authManager->getRole('engineering_department_manager');
                    break;
                default:
                    throw new \Exception('Unknown user type! Failed to assign permission to this user.');
                    break;
            }

            $authManager->assign($permission, $this->id);
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public static function statusLabels()
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_BANNED => 'مسدود',
            self::STATUS_NOT_ACTIVE => 'غیر فعال',
            self::STATUS_SOFT_DELETED => 'حذف',
        ];
    }

    public function getStatusLabel()
    {
        $labels = static::statusLabels();
        return $labels[$this->status];
    }

    public static function typeLabels()
    {
        return [
            // self::TYPE_REGULAR => 'کاربر عادی',
            self::TYPE_EXPERT => 'کاربر عادی',
            // self::TYPE_OPERATOR => 'اپراتور',
            // self::TYPE_EDITOR => 'سردبیر',
            self::TYPE_SUPERUSER => 'مدیر', // role = superuser
            self::TYPE_DEPARTMENT_MANAGER_PROCESS => 'مسئول قسمت فرایند',
            self::TYPE_DEPARTMENT_MANAGER_ENGINEERING => 'مسئول قسمت فنی'
        ];
    }

    public static function adminTypeLabels()
    {
        return [
            self::TYPE_EXPERT => 'کاربر عادی',
            self::TYPE_SUPERUSER => 'مدیر',
            self::TYPE_DEPARTMENT_MANAGER_PROCESS => 'مسئول قسمت فرایند',
            self::TYPE_DEPARTMENT_MANAGER_ENGINEERING => 'مسئول قسمت فنی'
        ];
    }

    public function getTypeLabel()
    {
        $labels = static::typeLabels();
        return $labels[$this->type];
    }
}
