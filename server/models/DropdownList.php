<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\ArrayHelper;

class DropdownList extends Model
{
    public static function getDropdownCities()
    {
        return [
            'Азов' => 'Азов',
            'Аксай' => 'Аксай',
            'Батайск' => 'Батайск',
            'Белая Калитва' => 'Белая Калитва',
            'Волгодонск' => 'Волгодонск',
            'Гуково' => 'Гуково',
            'Донецк' => 'Донецк',
            'Зверево' => 'Зверево',
            'Зерноград' => 'Зерноград',
            'Каменск-Шахтинский' => 'Каменск-Шахтинский',
            'Константиновск' => 'Константиновск',
            'Миллерово' => 'Миллерово',
            'Морозовск' => 'Морозовск',
            'Новочеркасск' => 'Новочеркасск',
            'Новошахтинск' => 'Новошахтинск',
            'Пролетарск' => 'Пролетарск',
            'Ростов-на-Дону' => 'Ростов-на-Дону',
            'Сальск' => 'Сальск',
            'Семикаракорск' => 'Семикаракорск',
            'Красный Сулин' => 'Красный Сулин',
            'Таганрог' => 'Таганрог',
            'Цимлянск' => 'Цимлянск',
            'Шахты' => 'Шахты'
        ];
    }

    public static function getDropdownYandexCoords()
    {
        return [
            'Азов' => [
                'latitude' => 47.112448,
                'longitude' => 39.423581
            ],
            'Аксай' => [
                'latitude' => 47.269914,
                'longitude' => 39.862283
            ],
            'Батайск' => [
                'latitude' => 47.138333,
                'longitude' => 39.744469
            ],
            'Белая Калитва' => [
                'latitude' => 48.177644,
                'longitude' => 40.802388
            ],
            'Волгодонск' => [
                'latitude' => 47.519624,
                'longitude' => 42.206329
            ],
            'Гуково' => [
                'latitude' => 48.061025,
                'longitude' => 39.934929
            ],
            'Донецк' => [
                'latitude' => 48.337341,
                'longitude' => 39.944721
            ],
            'Зверево' => [
                'latitude' => 48.042854,
                'longitude' => 40.126396
            ],
            'Зерноград' => [
                'latitude' => 48.849564,
                'longitude' => 40.312824
            ],
            'Каменск-Шахтинский' => [
                'latitude' => 48.323133,
                'longitude' => 40.269534
            ],
            'Константиновск' => [
                'latitude' => 47.577341,
                'longitude' => 41.096694
            ],
            'Миллерово' => [
                'latitude' => 48.921730,
                'longitude' => 40.394849
            ],
            'Морозовск' => [
                'latitude' => 48.351157,
                'longitude' => 41.830887
            ],
            'Новочеркасск' => [
                'latitude' => 47.422052,
                'longitude' => 40.093734
            ],
            'Новошахтинск' => [
                'latitude' => 47.754315,
                'longitude' => 39.934705
            ],
            'Пролетарск' => [
                'latitude' => 46.703855,
                'longitude' => 41.727544
            ],
            'Ростов-на-Дону' => [
                'latitude' => 47.222078,
                'longitude' => 39.720358
            ],
            'Сальск' => [
                'latitude' => 46.474898,
                'longitude' => 41.541503
            ],
            'Семикаракорск' => [
                'latitude' => 47.517798,
                'longitude' => 40.811505
            ],
            'Красный Сулин' => [
                'latitude' => 47.893519,
                'longitude' => 40.057495
            ],
            'Таганрог' => [
                'latitude' => 47.220646,
                'longitude' => 38.914722
            ],
            'Цимлянск' => [
                'latitude' => 47.646955,
                'longitude' => 42.104783
            ],
            'Шахты' => [
                'latitude' => 47.709237,
                'longitude' => 40.215401
            ],
        ];
    }

    public static function getDropdownYandexOrders()
    {
        $items = [];
        $orders = Order::find()->all();
        $coords = self::getDropdownYandexCoords();
        foreach ($orders as $order) {
            $item = [];
            $item['latitude'] = $coords[$order->city]['latitude'];
            $item['longitude'] = $coords[$order->city]['longitude'];
            $user = User::findOne(['id' => $order->user_id]);
            $item['options'] = [
                [
//                    'hintContent' => 'Подсказка при наведении на маркет',
                    'balloonContentHeader' => $user->lastname . ' ' . $user->firstname,
                    'balloonContentBody' => $order->text,
                    'balloonContentFooter' => $order->city . ', ' . $order->address,
                ]
            ];
            array_push($items, $item);
        }
        return $items;
    }
}