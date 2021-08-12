<?php

namespace app\models\search;

use yii\base\Model;
use app\models\Order;
use yii\data\ActiveDataProvider;

class OrderSearch extends Order
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id'], 'integer'],
            [['text', 'city', 'address'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Order::find()
            ->where(['user_id' => \Yii::$app->user->id])
            ->joinWith('user');

        $query->groupBy('order.id');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $dataProvider->setSort([
            'defaultOrder' => ['id' => SORT_DESC]
        ]);

        $query->andFilterWhere(['order.id' => $this->id]);

        return $dataProvider;
    }
}
