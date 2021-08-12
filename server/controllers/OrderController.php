<?php

namespace app\controllers;

use app\models\Order;
use app\models\search\OrderSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class OrderController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Order model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Order();
        if ($model->load(Yii::$app->request->post()))
        {
            $model->user_id = Yii::$app->user->id;
            $model->save();

            return $this->redirect(['/site/index']);
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates Order model.
     * If updates is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = Order::findOne(['id' => $id]);
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['index']);
        }
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Views Order model.
     * @return mixed
     */
    public function actionView($id)
    {
        $model = Order::findOne(['id' => $id]);
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Order model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = Order::findOne(['id' => $id]);
        $model->delete();

        return $this->redirect(['index']);
    }
}