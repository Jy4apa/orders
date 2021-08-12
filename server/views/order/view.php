<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Order */

$this->title = 'Просмотр заявки';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="order-view">

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap
        align-items-center p-3 mb-3 bg-light">

        <h1 class="backend-title"><?= Html::encode($this->title) ?></h1>

        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="ml-2">
                <?= Html::a('Закрыть ', ['index'], ['class' => 'btn btn-light']) ?>

            </div>
        </div>
    </div>

    <div class="container-fluid">

        <?= $this->render('_form', [
            'model' => $model,
            'readOnly' => true
        ]) ?>

    </div>

</div>
