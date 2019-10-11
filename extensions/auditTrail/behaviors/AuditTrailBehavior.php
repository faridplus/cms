<?php

namespace extensions\auditTrail\behaviors;

use yii;
use yii\helpers\Json;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class AuditTrailBehavior extends \yii\base\Behavior
{
    const IS_FIELD_UPDATED_YES = 1;
    const IS_FIELD_UPDATED_NO = 2;

    const IS_FIELD_RELATION_YES = 1;
    const IS_FIELD_RELATION_NO = 2;

    const tableName = 'audit_trail';

    public $relations; // relation can be objects (instances of ActiveQueryInterface) or simple strings

    private $updateTime;

    public function init()
    {
        if (isset($this->relations) && !is_array($this->relations)) {
            throw new InvalidConfigException('`relations` property must be an array.');
        }
        if (!isset($this->relations)) {
            $this->relations = [];
        }

        parent::init();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'logRelations', // $isAfterUpdate = false
            ActiveRecord::EVENT_AFTER_UPDATE => 'logAttributesAndRelations' // $isAfterUpdate = true
        ];
    }

    public function logAttributesAndRelations($event)
    {
        $this->updateTime = time();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->logAttributes($event);
            $this->logRelations($event, true);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function logAttributes($event)
    {
        $changedAttributes = $event->changedAttributes;
        $ownerId = $this->owner->id;
        $ownerClassName = (new \ReflectionClass($this->owner))->getName();

        $rows= [];
        foreach ($this->owner->getAttributes() as $name => $newValue) {
            $row['ownerId'] = $ownerId;
            $row['fieldName'] =  $name;
            $row['fieldNewValue'] =  $newValue;
            if(is_array($changedAttributes) && ArrayHelper::keyExists($name, $changedAttributes) && $changedAttributes[$name] != $newValue){
                $row['fieldOldValue'] =  $changedAttributes[$name];
                $row['isUpdated'] = self::IS_FIELD_UPDATED_YES;
            }else{
                $row['fieldOldValue'] =  $newValue; // in this case, both new and old values are the same
                $row['isUpdated'] = self::IS_FIELD_UPDATED_NO;
            }
            $row['isRelation'] = self::IS_FIELD_RELATION_NO;
            $row['ownerClassName'] = $ownerClassName;
            $row['updatedAt'] = $this->updateTime;
            $row['updatedBy'] = Yii::$app->get('user')->id;

            $rows[]=$row;
        }

        if (!empty($rows)) {
            Yii::$app->db->createCommand()->batchInsert(
                self::tableName,
                ['ownerId', 'fieldName', 'fieldNewValue', 'fieldOldValue' , 'isUpdated', 'isRelation', 'ownerClassName', 'updatedAt', 'updatedBy'],
                $rows
            )->execute();
        }
    }

    // TODO implement ActiveQueryInterface relations for input param of Json::encode()
    public function logRelations($event, $isAfterUpdate = false)
    {
        $ownerId = $this->owner->id;
        $ownerClassName = (new \ReflectionClass($this->owner))->getName();

        $rows = [];
        foreach ($this->relations as $relationName) {
            try {
                $relationGetter = 'get' . ucFirst($relationName);
                $relationValue = Json::encode($this->owner->$relationGetter());
            }catch(Exception $e){ // makes sure that relation exists and can be called
                throw new InvalidConfigException("Relation `{$relationName}` is not valid for model class `{$ownerClassName}`. Exception message: " . $e->getMessage());
            }

            if($isAfterUpdate){
                $row['fieldNewValue'] = trim($relationValue, '"');
            }else{
                $row['fieldOldValue'] = trim($relationValue, '"');
                $row['fieldNewValue'] = null; // we will fill it after update
            }
            $row['isUpdated'] = null; // we will fill it after update
            $row['ownerId'] = $ownerId;
            $row['fieldName'] = $relationName;
            $row['isRelation'] = self::IS_FIELD_RELATION_YES;
            $row['ownerClassName'] = $ownerClassName;
            $row['updatedAt'] = $this->updateTime;
            $row['updatedBy'] = Yii::$app->get('user')->id;

            if($isAfterUpdate){
                // fill fieldNewValue column
                Yii::$app->db->createCommand()->update(
                    self::tableName,
                    [
                        'fieldNewValue' => $row['fieldNewValue'],
                        'updatedAt' => $row['updatedAt']
                    ],
                    [
                        'ownerClassName'=> $row['ownerClassName'], // conditions
                        'ownerId'=>$row['ownerId'],
                        'fieldName' => $row['fieldName'],
                        'isRelation' => $row['isRelation'],
                        'fieldNewValue' => null
                    ]
                )->execute();

                // fill isUpdated column in 2 steps
                // first step for updated values:
                Yii::$app->db->createCommand()->update(
                    self::tableName,
                    [
                        'isUpdated' => self::IS_FIELD_UPDATED_YES
                    ],
                    [
                        'and',
                        'ownerClassName = :ownerClassName',
                        'ownerId = :ownerId',
                        'isRelation = :isRelation',
                        'fieldName = :fieldName',
                        'fieldNewValue != fieldOldValue'
                    ],
                    [
                        ':ownerClassName' => $row['ownerClassName'] ,
                        ':ownerId' => $row['ownerId'],
                        ':isRelation' => $row['isRelation'],
                        ':fieldName' => $row['fieldName']
                    ]
                )->execute();
                // second step for not updated values:
                Yii::$app->db->createCommand()->update(
                    self::tableName,
                    [
                        'isUpdated' => self::IS_FIELD_UPDATED_NO
                    ],
                    [
                        'and',
                        'ownerClassName = :ownerClassName',
                        'ownerId = :ownerId',
                        'isRelation = :isRelation',
                        'fieldName = :fieldName',
                        'fieldNewValue = fieldOldValue'
                    ],
                    [
                        ':ownerClassName' => $row['ownerClassName'] ,
                        ':ownerId' => $row['ownerId'],
                        ':isRelation' => $row['isRelation'],
                        ':fieldName' => $row['fieldName']
                    ]
                )->execute();
            }else{
                $rows[] = $row; // $rows to be inserted
            }
        }

        if (!empty($rows) && !$isAfterUpdate) {
            Yii::$app->db->createCommand()->batchInsert(
                self::tableName,
                ['fieldOldValue', 'fieldNewValue', 'isUpdated', 'ownerId', 'fieldName','isRelation', 'ownerClassName',  'updatedAt', 'updatedBy'],
                $rows
            )->execute();
        }
    }

    /**
     * if includeFields is not empty, the value of excludeFields does NOT matter.
     *
     * @param array $includeFields e.g. ['reasonForGenesis', 'necessity']
     * @param array $excludeFields e.g. ['updatedAt', 'updatedBy']
     * @return void
     */
    public function getLogsRaw($includeFields = [], $excludeFields = [], $onlyChangedFields = false, $sortType = 'DESC')
    {
        $ownerId = $this->owner->id;
        $ownerClassName = (new \ReflectionClass($this->owner))->getName();
        $tableName = self::tableName;

        $includeConditionParams = [];
        $excludeConditionParams = [];
        $includeConditionString = '';
        $excludeConditionString = '';
        if(!empty($includeFields)){
            $includeConditionString = ' AND ' . \Yii::$app->db->getQueryBuilder()->buildCondition(['IN', 'fieldName', $includeFields], $includeConditionParams);
        }
        else if(!empty($excludeFields)){
            $excludeConditionString = ' AND ' . \Yii::$app->db->getQueryBuilder()->buildCondition(['NOT IN', 'fieldName', $excludeFields], $excludeConditionParams);
        }

        $onlyChangedFieldsCondition = '';
        if($onlyChangedFields){
            $onlyChangedFieldsCondition = ' AND isUpdated = :isUpdated';
        }

        $logs = Yii::$app->db->createCommand("
            SELECT
                *
            FROM
                {$tableName}
            WHERE
                ownerClassName = :ownerClassName
                AND ownerId = :ownerId
                {$onlyChangedFieldsCondition}
                {$includeConditionString}
                {$excludeConditionString}
                AND updatedAt IN (
                SELECT
                    updatedAt
                FROM
                    {$tableName}
                WHERE
                    ownerClassName = :ownerClassName
                    AND ownerId = :ownerId
                    AND isUpdated = :isUpdated
                    {$includeConditionString}
                    {$excludeConditionString}
                )
            ORDER BY
                updatedAt {$sortType}
            ",
            array_merge(
                [
                    ':ownerClassName' => $ownerClassName,
                    ':ownerId' => $ownerId,
                    ':isUpdated' => self::IS_FIELD_UPDATED_YES,
                ],
                $includeConditionParams,
                $excludeConditionParams
            )
        )->queryAll();

        return $logs;
    }

    /**
     * if includeFields is not empty, the value of excludeFields does NOT matter.
     *
     * @param array $includeFields e.g. ['reasonForGenesis', 'necessity']
     * @param array $excludeFields e.g. ['updatedAt', 'updatedBy']
     * @return void
     */
    public function getLogsGroupedByUpdateTime($includeFields = [], $excludeFields = [], $onlyChangedFields = false, $sortType = 'DESC')
    {
        $logsRaw = $this->owner->getLogsRaw($includeFields, $excludeFields, $onlyChangedFields, $sortType);
        $result = ArrayHelper::map($logsRaw, 'fieldName', 'fieldOldValue', 'updatedAt');

        return $result;
    }
}
