<?php

use app\models\DropdownList;
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Order */

?>
<div class="order-form">
    <?php
    $form = ActiveForm::begin([
        'id' => 'form-model',
        'options' => [
            'enctype' => 'multipart/form-data'
        ],
    ]);
    ?>
    <?php if ($readOnly): ?>
        <div class="row">

            <div class="col-sm-6">
                <?= $form->field($model, 'text')->textarea(['rows' => 6, 'disabled' => true]) ?>
                <?= $form->field($model, 'city')->dropdownList(
                    DropdownList::getDropdownCities(), ['prompt' => 'Выберите город', 'disabled' => true]) ?>
                <?= $form->field($model, 'address')->textInput(['maxlength' => 255, 'disabled' => true]) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'text')->textarea(['rows' => 6]) ?>
                <?= $form->field($model, 'city')->dropdownList(
                        DropdownList::getDropdownCities(), ['prompt' => 'Выберите город']) ?>
                <?= $form->field($model, 'address')->textInput(['maxlength' => 255]) ?>
            </div>
        </div>
        <div class="form-group">
            <?= Html::submitButton('Опубликовать', ['class' => 'btn btn-success']) ?>
        </div>
    <?php endif; ?>
    <?php ActiveForm::end(); ?>
</div>
