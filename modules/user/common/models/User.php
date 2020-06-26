<?php

namespace modules\user\common\models;

use Yii;
use yii\db\ActiveRecord;
use nad\office\modules\expert\models\Expert;

class User extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_BANNED = 3;
    const STATUS_SOFT_DELETED = 4;
    const TYPE_REGULAR = 1;
    const TYPE_OPERATOR = 2;
    const TYPE_EDITOR = 3;
    const TYPE_SUPERUSER = 4;

    public $password;

    public static function tableName()
    {
        return 'user';
    }

    public function behaviors()
    {
        return [
            'core\behaviors\TimestampBehavior'
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert || $this->scenario == 'changePassword') {
                $this->setRandomToken();
                $this->setPassword();
                $this->authKey = Yii::$app->security->generateRandomString();
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($insert){
            $expert = new Expert();
            $expert->userId = $this->getPrimaryKey();
            $expert->personnelId = $this->personnelId;
            $expert->departmentId = 0; // a dummy value which stands for PROCESS
            $expert->save();
        }elseif($this->scenario == 'update'){
            $expert = Expert::find()->where(['userId' => $this->id])->one();
            $expert->personnelId = $this->personnelId;
            $expert->save();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function getFullName()
    {
        return "$this->name $this->surname";
    }

    public function setPassword()
    {
        $this->passwordHash = Yii::$app->security->generatePasswordHash($this->password);
    }

    public function setRandomToken()
    {
        $this->randomToken = Yii::$app->security->generateRandomString();
    }

    public function updateFailedAttempts()
    {
        if (Yii::$app->setting->get('website.deactiveUser')) {
            $maxFailedAttempt = (int)Yii::$app->setting->get(
                'website.failedLoginAttempts'
            );
            self::updateAllCounters(['failedAttempts'=>'1'], [
                'email'=>$this->email
            ]);
            if ($this->failedAttempts+1 == $maxFailedAttempt) {
                $this->status = self::STATUS_BANNED;
                $this->failedAttempts = 0;
                $this->update();
                Yii::$app->session->addFlash(
                    'danger',
                    'به منظور حفظ امنیت، این حساب کاربری مسدود شد.
                    برای رفع مشکل رمز عبور خود را تغییر دهید.'
                );
            }
        }
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    public function getExpert()
    {
        return $this->hasOne(Expert::class, ['userId' => 'id']);
    }
}
