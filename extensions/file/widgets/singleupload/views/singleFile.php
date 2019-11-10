<?php
use yii\web\View;
use yii\helpers\Html;

$hasError = $fileObject->hasErrors() ? 'has-error' : '';
?>
<?= Html::activeInput('hidden', $fileObject, "[{$uniqueId}]group") ?>
<?= Html::activeInput('hidden', $fileObject, "[{$uniqueId}]folderName") ?>
<?= Html::input('hidden', 'delete-url', \yii\helpers\Url::toRoute('/file/ajax-delete')) ?>

<div class="form-group filemanager-widgets-file <?php echo $hasError ?>">
    <?php if (isset($this->context->label)): ?>
        <label class="control-label"><?php echo $this->context->label ?></label>
    <?php endif ?>
    <div class="input-group single-file-upload" style="display:<?php echo $inputDisplay ?>">
        <span class="input-group-btn">
            <span class="btn btn-primary btn-file">
                انتخاب <?= Html::activeFileInput($fileObject, "[{$uniqueId}]resource") ?>
            </span>
        </span>
        <input type="text" class="form-control" readonly>
    </div>
    <?= Html::error($fileObject, 'resource', ['class' => 'help-block']) ?>

    <div class="uploaded-files">
        <ul class="list-unstyled">
            <?php foreach ($uploadedFiles as $file): ?>
                <li>
                    <?php echo Html::a(
                        '<i class="fa fa-download text-success"></i>',
                        $file->url,
                        [
                            'title' => 'دانلود',
                        ]
                    ) ?>
                    <span class="filename">
                        <?php
                            $fileNamelabel = $file->originalName;
                            echo ((mb_strlen($fileNamelabel) > 19 ) ? mb_substr($fileNamelabel, 0, 19) . '...' : $fileNamelabel);
                        ?></span>
                    <i
                        title = "حذف"
                        class="fa fa-trash fa-lg text-danger file-delete"
                        data-id="<?php echo $file->id ?>"
                    ></i>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</div>
