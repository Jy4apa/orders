<?php

use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use app\models\Order;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Мои заявки';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="order-index">

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap
        align-items-center p-3 mb-3 bg-light">

        <h1 class="backend-title"><?= Html::encode($this->title) ?></h1>

        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="ml-2">
                <?= Html::a('Подать заявку', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'columns' => [
                [
                    'attribute' => 'id',
                    'headerOptions' => ['width' => '90'],
                    'filter' => true,
                    'enableSorting' => true
                ],
                [
                    'attribute' => 'text',
                    'filter' => false,
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'city',
                    'filter' => false,
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'address',
                    'filter' => false,
                    'enableSorting' => false
                ],
                [
                    'class' => ActionColumn::className(),
                    'headerOptions' => ['width' => '100'],

                ],
            ],
        ]); ?>

    </div>
</div>