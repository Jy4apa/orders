<?php

namespace app\models;

/**
 * @property int $id
 * @property int $user_id
 * @property string $text
 * @property string $city
 * @property string $address
 */

class Order extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'city', 'address'], 'trim'],
            [['text', 'city', 'address'], 'required'],
            ['text', 'string'],
            ['address', 'string', 'max' => 255],
            ['city', 'string', 'max' => 150],
            ['user_id', 'integer']
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'ID пользователя',
            'text' => 'Текст заявки',
            'city' => 'Город',
            'address' => 'Адрес',
        ];
    }
}