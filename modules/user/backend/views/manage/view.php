<?php
use theme\widgets\Panel;
use yii\widgets\DetailView;
use theme\widgets\ActionButtons;
use modules\user\backend\models\User;
use nad\office\modules\expert\models\Expert;

$this->title = $model->email;
$this->params['breadcrumbs'][] = ['label' => 'کاربران', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<?= ActionButtons::widget([
    'modelID' => $model->id,
    'buttons' => [
        'update' => ['label' => 'ویرایش'],
        'change-password' => [
            'icon' => 'key',
            'type' => 'warning',
            'label' => 'تغییر رمز عبور',
            'url' => ['change-password', 'id' => $model->id]
        ],
        'create' => ['label' => 'کاربر جدید'],
        'index' => ['label' => 'کاربران'],
    ],
]); ?>
<?php Panel::begin([
    'title' => $model->email,
]) ?>
    <div class="user-view">
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id:farsiNumber',
                'name',
                'surname',
                'post',
                [
                    'attribute' => 'personnelId',
                    'value' => function($model){
                        return Expert::find()->where(['userId' => $model->id])->one()->personnelId;
                    },
                ],
                [
                    'attribute' => 'email',
                    'contentOptions' => ['style' => 'direction:ltr; text-align:right']
                ],
                [
                    'attribute' => 'originalPassword',
                    'contentOptions' => ['style' => 'direction:ltr; text-align:right']
                ],
                'phone',
                [
                    'attribute' => 'status',
                    'value' => $model->getStatusLabel(),
                ],
                'createdAt:datetime',
                'lastLoggedInAt:datetime',
                [
                    'attribute' => 'type',
                    'value' => $model->getTypeLabel(),
                ],
            ],
        ]) ?>
    </div>
<?php Panel::end() ?>
